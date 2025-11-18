<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class ClassNameVisitorTest extends TestCase
{
    public function testGetClassNameReturnsFullyQualifiedClassName(): void
    {
        $code = <<<'PHP'
<?php
namespace Foo\Bar;

class Baz {}
PHP;

        $parser     = new ParserFactory()->createForNewestSupportedVersion();
        $statements = $parser->parse($code);

        $nodeTraverser    = new NodeTraverser();
        $classNameVisitor = new ClassNameVisitor();
        $nodeTraverser->addVisitor($classNameVisitor);
        $nodeTraverser->traverse($statements);

        self::assertSame('Foo\Bar\Baz', $classNameVisitor->getClassName());
    }

    public function testGetClassNameWithoutNamespace(): void
    {
        $code = <<<'PHP'
<?php
class SimpleClass {}
PHP;

        $parser     = new ParserFactory()->createForNewestSupportedVersion();
        $statements = $parser->parse($code);

        $nodeTraverser    = new NodeTraverser();
        $classNameVisitor = new ClassNameVisitor();
        $nodeTraverser->addVisitor($classNameVisitor);
        $nodeTraverser->traverse($statements);

        self::assertSame('SimpleClass', $classNameVisitor->getClassName());
    }

    public function testGetClassNameWithGlobalNamespace(): void
    {
        $code = <<<'PHP'
<?php
namespace {
    class GlobalClass {}
}
PHP;

        $parser     = new ParserFactory()->createForNewestSupportedVersion();
        $statements = $parser->parse($code);

        $nodeTraverser    = new NodeTraverser();
        $classNameVisitor = new ClassNameVisitor();
        $nodeTraverser->addVisitor($classNameVisitor);
        $nodeTraverser->traverse($statements);

        self::assertSame('GlobalClass', $classNameVisitor->getClassName());
    }

    public function testEnterNodeWithNamespaceNode(): void
    {
        $classNameVisitor = new ClassNameVisitor();
        $namespace        = new Namespace_(new Name('Test\Namespace'));

        $classNameVisitor->enterNode($namespace);

        self::assertSame('Test\Namespace', $classNameVisitor->getClassName());
    }

    public function testEnterNodeWithClassNode(): void
    {
        $classNameVisitor = new ClassNameVisitor();
        $class            = new Class_(new Identifier('TestClass'));

        $classNameVisitor->enterNode($class);

        self::assertSame('TestClass', $classNameVisitor->getClassName());
    }

    public function testGetClassNameReturnsEmptyStringWhenNoClassFound(): void
    {
        $code = <<<'PHP'
<?php
// Just a comment, no class
PHP;

        $parser     = new ParserFactory()->createForNewestSupportedVersion();
        $statements = $parser->parse($code);

        $nodeTraverser    = new NodeTraverser();
        $classNameVisitor = new ClassNameVisitor();
        $nodeTraverser->addVisitor($classNameVisitor);
        $nodeTraverser->traverse($statements);

        self::assertSame('', $classNameVisitor->getClassName());
    }
}
