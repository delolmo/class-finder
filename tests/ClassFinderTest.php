<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder;

use ArrayIterator;
use DateTimeInterface;
use DelOlmo\ClassFinder\Fixtures\AbstractClass;
use DelOlmo\ClassFinder\Fixtures\InterfaceFile;
use DelOlmo\ClassFinder\Fixtures\ValidClass;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Some\Random\Namespace\That\Does\Not\Match\Autoload\NonAutoloadableClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function file_get_contents;
use function restore_error_handler;
use function set_error_handler;

final class ClassFinderTest extends TestCase
{
    public function testFindAllReturnsInstantiableClassesFromDirectory(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findAll(__DIR__ . '/../src');

        self::assertIsIterable($classes);
        self::assertContains(ClassFinder::class, $classes);
        self::assertContains(ClassNameVisitor::class, $classes);
    }

    public function testFindByClassNameReturnsSubclassesOnly(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findByClassName(NodeVisitorAbstract::class, __DIR__ . '/../src');

        self::assertIsIterable($classes);
        self::assertContains(ClassNameVisitor::class, $classes);
        self::assertNotContains(ClassFinder::class, $classes);
    }

    public function testFindAllExcludesNonInstantiableClasses(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findAll(__DIR__ . '/../src');

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            self::assertTrue(
                $reflection->isInstantiable(),
                'Expected only instantiable classes, but found non-instantiable: ' . $class,
            );
            self::assertFalse(
                $reflection->isAbstract(),
                'Expected only instantiable classes, but found abstract: ' . $class,
            );
            self::assertFalse(
                $reflection->isInterface(),
                'Expected only instantiable classes, but found interface: ' . $class,
            );
        }
    }

    public function testFindByClassNameReturnsEmptyListWhenNoSubclassesFound(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findByClassName(DateTimeInterface::class, __DIR__ . '/../src');

        self::assertIsIterable($classes);
        self::assertEmpty($classes);
    }

    public function testFindAllSkipsNonAutoloadableClasses(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertNotContains(
            NonAutoloadableClass::class,
            $classes,
        );
    }

    public function testFindAllSkipsAbstractClasses(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertNotContains(AbstractClass::class, $classes);
    }

    public function testFindAllSkipsInterfaces(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertNotContains(InterfaceFile::class, $classes);
    }

    public function testFindAllIncludesValidInstantiableClasses(): void
    {
        $classFinder = new ClassFinder();

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertContains(ValidClass::class, $classes);
    }

    public function testFindAllSkipsFilesWhenGetRealPathReturnsFalse(): void
    {
        $file = $this->createMock(SplFileInfo::class);
        $file->method('getRealPath')->willReturn(false);

        $finder = $this->createMock(Finder::class);
        $finder->method('files')->willReturnSelf();
        $finder->method('in')->willReturnSelf();
        $finder->method('name')->willReturnSelf();
        $finder->method('getIterator')->willReturn(new ArrayIterator([$file]));

        $classFinder = new ClassFinder($finder);

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertEmpty($classes);
    }

    public function testFindAllSkipsFilesWhenParserReturnsNull(): void
    {
        $file = $this->createMock(SplFileInfo::class);
        $file->method('getRealPath')->willReturn(__DIR__ . '/Fixtures/ValidClass.php');

        $finder = $this->createMock(Finder::class);
        $finder->method('files')->willReturnSelf();
        $finder->method('in')->willReturnSelf();
        $finder->method('name')->willReturnSelf();
        $finder->method('getIterator')->willReturn(new ArrayIterator([$file]));

        $parser = $this->createMock(Parser::class);
        $parser->method('parse')->willReturn(null);

        $classFinder = new ClassFinder($finder, $parser);

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertEmpty($classes);
    }

    public function testFindAllSkipsFilesWhenFileGetContentsReturnsFalse(): void
    {
        $file = $this->createMock(SplFileInfo::class);
        $file->method('getRealPath')->willReturn('/path/to/nonexistent/file.php');

        $finder = $this->createMock(Finder::class);
        $finder->method('files')->willReturnSelf();
        $finder->method('in')->willReturnSelf();
        $finder->method('name')->willReturnSelf();
        $finder->method('getIterator')->willReturn(new ArrayIterator([$file]));

        $classFinder = new ClassFinder($finder);

        set_error_handler(static fn (): bool => true);

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        restore_error_handler();

        self::assertIsIterable($classes);
        self::assertEmpty($classes);
    }

    public function testFindAllContinuesProcessingAfterGetRealPathFailure(): void
    {
        $failingFile = $this->createMock(SplFileInfo::class);
        $failingFile->method('getRealPath')->willReturn(false);

        $validFile = $this->createMock(SplFileInfo::class);
        $validFile->method('getRealPath')->willReturn(__DIR__ . '/Fixtures/ValidClass.php');

        $finder = $this->createMock(Finder::class);
        $finder->method('files')->willReturnSelf();
        $finder->method('in')->willReturnSelf();
        $finder->method('name')->willReturnSelf();
        $finder->method('getIterator')->willReturn(new ArrayIterator([$failingFile, $validFile]));

        $classFinder = new ClassFinder($finder);

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertContains(ValidClass::class, $classes);
    }

    public function testFindAllContinuesProcessingAfterFileGetContentsFailure(): void
    {
        $failingFile = $this->createMock(SplFileInfo::class);
        $failingFile->method('getRealPath')->willReturn('/path/to/nonexistent/file.php');

        $validFile = $this->createMock(SplFileInfo::class);
        $validFile->method('getRealPath')->willReturn(__DIR__ . '/Fixtures/ValidClass.php');

        $finder = $this->createMock(Finder::class);
        $finder->method('files')->willReturnSelf();
        $finder->method('in')->willReturnSelf();
        $finder->method('name')->willReturnSelf();
        $finder->method('getIterator')->willReturn(new ArrayIterator([$failingFile, $validFile]));

        $classFinder = new ClassFinder($finder);

        set_error_handler(static fn (): bool => true);

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        restore_error_handler();

        self::assertIsIterable($classes);
        self::assertContains(ValidClass::class, $classes);
    }

    public function testFindAllContinuesProcessingAfterParserReturnsNull(): void
    {
        $file1 = $this->createMock(SplFileInfo::class);
        $file1->method('getRealPath')->willReturn(__DIR__ . '/Fixtures/ValidClass.php');

        $file2 = $this->createMock(SplFileInfo::class);
        $file2->method('getRealPath')->willReturn(__DIR__ . '/Fixtures/BaseClass.php');

        $finder = $this->createMock(Finder::class);
        $finder->method('files')->willReturnSelf();
        $finder->method('in')->willReturnSelf();
        $finder->method('name')->willReturnSelf();
        $finder->method('getIterator')->willReturn(new ArrayIterator([$file1, $file2]));

        $validAst = new ParserFactory()
            ->createForNewestSupportedVersion()
            ->parse(file_get_contents(__DIR__ . '/Fixtures/ValidClass.php'));

        $parser = $this->createMock(Parser::class);
        $parser->method('parse')->willReturnOnConsecutiveCalls(null, $validAst);

        $classFinder = new ClassFinder($finder, $parser);

        $classes = $classFinder->findAll(__DIR__ . '/Fixtures');

        self::assertIsIterable($classes);
        self::assertCount(1, $classes);
        self::assertContains(ValidClass::class, $classes);
    }

    public function testFindByClassNameContinuesProcessingAfterNonSubclass(): void
    {
        $classFinder = new ClassFinder();

        $classes = [...$classFinder->findByClassName(NodeVisitorAbstract::class, __DIR__ . '/Fixtures')];

        self::assertIsIterable($classes);
        self::assertEmpty($classes, 'Fixtures should have no NodeVisitorAbstract subclasses');

        $classesFromSrc = [...$classFinder->findByClassName(NodeVisitorAbstract::class, __DIR__ . '/../src')];

        self::assertNotEmpty($classesFromSrc);
        self::assertContains(ClassNameVisitor::class, $classesFromSrc);
    }
}
