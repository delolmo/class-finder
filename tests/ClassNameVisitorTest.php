<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder\Tests;

use DelOlmo\ClassFinder\ClassNameVisitor;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassNameVisitor::class)]
final class ClassNameVisitorTest extends TestCase
{
    /** @param list<string> $expected */
    #[DataProvider('declarationProvider')]
    #[TestDox('getClassNames() returns $_dataName')]
    public function testGetClassNamesReturnsDeclaredClasses(string $code, array $expected): void
    {
        $classNameVisitor = $this->traverse($code);

        Assert::assertSame($expected, $classNameVisitor->getClassNames());
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function declarationProvider(): iterable
    {
        yield 'a namespaced class' => ["<?php namespace Foo\\Bar;\nclass Baz {}", ['Foo\Bar\Baz']];

        yield 'a class without namespace' => ['<?php class SimpleClass {}', ['SimpleClass']];

        yield 'a class in the braced global namespace' => ['<?php namespace { class GlobalClass {} }', ['GlobalClass']];

        yield 'classes in several namespaces, in order' => [
            '<?php namespace A { class First {} } namespace B { class Second {} } namespace { class Third {} }',
            ['A\First', 'B\Second', 'Third'],
        ];

        yield 'no anonymous class' => ['<?php namespace Foo; $object = new class {};', []];

        yield 'no interface, trait or enum' => ['<?php interface I {} trait T {} enum E {}', []];

        yield 'nothing for a file without classes' => ['<?php // Just a comment', []];
    }

    #[TestDox('getClassName() returns the last namespace joined to the last class name')]
    public function testGetClassNameReturnsFullyQualifiedClassName(): void
    {
        $classNameVisitor = $this->traverse("<?php namespace Foo\\Bar;\nclass Baz {}");

        Assert::assertSame('Foo\Bar\Baz', $classNameVisitor->getClassName());
    }

    #[TestDox('getClassName() returns the namespace alone when no class was entered')]
    public function testGetClassNameReturnsNamespaceWithoutClass(): void
    {
        $classNameVisitor = new ClassNameVisitor();

        $classNameVisitor->enterNode(new Namespace_(new Name('Test\Namespace')));

        Assert::assertSame('Test\Namespace', $classNameVisitor->getClassName());
    }

    #[TestDox('getClassName() returns the class name alone when no namespace was entered')]
    public function testGetClassNameReturnsClassWithoutNamespace(): void
    {
        $classNameVisitor = new ClassNameVisitor();

        $classNameVisitor->enterNode(new Class_(new Identifier('TestClass')));

        Assert::assertSame('TestClass', $classNameVisitor->getClassName());
    }

    #[TestDox('getClassName() returns an empty string for a file without classes')]
    public function testGetClassNameReturnsEmptyStringWithoutClass(): void
    {
        $classNameVisitor = $this->traverse('<?php // Just a comment');

        Assert::assertSame('', $classNameVisitor->getClassName());
    }

    private function traverse(string $code): ClassNameVisitor
    {
        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse($code) ?? [];

        $classNameVisitor = new ClassNameVisitor();

        new NodeTraverser($classNameVisitor)->traverse($statements);

        return $classNameVisitor;
    }
}
