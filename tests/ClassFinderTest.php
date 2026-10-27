<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder\Tests;

use DateTimeInterface;
use DelOlmo\ClassFinder\ClassFinder;
use DelOlmo\ClassFinder\ClassNameVisitor;
use DelOlmo\ClassFinder\Tests\Fixtures\BaseClass;
use DelOlmo\ClassFinder\Tests\Fixtures\ExtendsBaseClass;
use DelOlmo\ClassFinder\Tests\Fixtures\ImplementsContract;
use DelOlmo\ClassFinder\Tests\Fixtures\TestContract;
use DelOlmo\ClassFinder\Tests\Fixtures\TwoClasses;
use DelOlmo\ClassFinder\Tests\Fixtures\TwoClassesSibling;
use DelOlmo\ClassFinder\Tests\Fixtures\ValidClass;
use Override;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

use function chmod;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(ClassFinder::class)]
#[UsesClass(ClassNameVisitor::class)]
final class ClassFinderTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/Fixtures';

    // The text of a valid class whose real declaration is autoloadable, so a
    // copy of it in a scratch directory is reported as found.
    private const string VALID_CLASS_SOURCE = <<<'PHP'
        <?php

        namespace DelOlmo\ClassFinder\Tests\Fixtures;

        final class ValidClass
        {
        }
        PHP;

    private string $scratch;

    /** @var list<string> */
    private array $scratchFiles = [];

    #[Override]
    protected function setUp(): void
    {
        $this->scratch = sys_get_temp_dir() . '/class-finder-' . uniqid();

        mkdir($this->scratch);
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach ($this->scratchFiles as $scratchFile) {
            chmod($scratchFile, 0o644);
            unlink($scratchFile);
        }

        rmdir($this->scratch);
    }

    #[TestDox('findAll() returns every instantiable, autoloadable class, several per file included')]
    public function testFindAllReturnsInstantiableAutoloadableClasses(): void
    {
        $classes = new ClassFinder()->findAll(self::FIXTURES);

        Assert::assertEqualsCanonicalizing([
            ExtendsBaseClass::class,
            ImplementsContract::class,
            TwoClasses::class,
            TwoClassesSibling::class,
            ValidClass::class,
        ], $classes);
    }

    #[TestDox('findAll() searches only the directory of the call, not those of earlier calls')]
    public function testFindAllSearchesOnlyTheGivenDirectoryOnRepeatedCalls(): void
    {
        $classFinder = new ClassFinder();

        $classFinder->findAll(self::FIXTURES);

        $classes = $classFinder->findAll(__DIR__ . '/../src');

        Assert::assertEqualsCanonicalizing([
            ClassFinder::class,
            ClassNameVisitor::class,
        ], $classes);
    }

    #[TestDox('findAll() keeps the filters of the Finder it was given')]
    public function testFindAllKeepsTemplateFinderFilters(): void
    {
        $finder = new Finder()->notName('TwoClasses.php');

        $classes = new ClassFinder($finder)->findAll(self::FIXTURES);

        Assert::assertNotContains(TwoClasses::class, $classes);
        Assert::assertContains(ValidClass::class, $classes);
    }

    #[TestDox('findAll() skips a file that does not parse and goes on with the next')]
    public function testFindAllSkipsFilesThatDoNotParse(): void
    {
        $this->writeScratchFile('a-broken.php', '<?php final class {');
        $this->writeScratchFile('b-valid.php', self::VALID_CLASS_SOURCE);

        $classes = new ClassFinder(new Finder()->sortByName())->findAll($this->scratch);

        Assert::assertSame([ValidClass::class], $classes);
    }

    #[TestDox('findAll() skips a file that cannot be read and goes on with the next')]
    public function testFindAllSkipsFilesThatCannotBeRead(): void
    {
        chmod($this->writeScratchFile('a-unreadable.php', self::VALID_CLASS_SOURCE), 0o000);
        $this->writeScratchFile('b-valid.php', self::VALID_CLASS_SOURCE);

        $classes = new ClassFinder(new Finder()->sortByName())->findAll($this->scratch);

        Assert::assertSame([ValidClass::class], $classes);
    }

    #[TestDox('findAll() reads only files with a .php extension')]
    public function testFindAllIgnoresFilesWithoutPhpExtension(): void
    {
        $this->writeScratchFile('valid.txt', self::VALID_CLASS_SOURCE);

        $classes = new ClassFinder()->findAll($this->scratch);

        Assert::assertSame([], $classes);
    }

    #[TestDox('findAll() skips a file the parser returns no statements for and goes on with the next')]
    public function testFindAllSkipsFilesWithoutStatements(): void
    {
        // Both files are empty: only the injected parser's statements for the
        // second one can yield a class.
        $this->writeScratchFile('a-empty.php', '');
        $this->writeScratchFile('b-empty.php', '');

        $statements = new ParserFactory()->createForNewestSupportedVersion()->parse(self::VALID_CLASS_SOURCE);

        $parser = self::createStub(Parser::class);
        $parser->method('parse')->willReturn(null, $statements);

        $classes = new ClassFinder(new Finder()->sortByName(), $parser)->findAll($this->scratch);

        Assert::assertSame([ValidClass::class], $classes);
    }

    /** @param non-empty-string $skippedDeclaration */
    #[DataProvider('skippedDeclarationProvider')]
    #[TestDox('findAll() skips $_dataName and goes on with the next class of the file')]
    public function testFindAllSkipsClassesAndGoesOnWithTheFile(string $skippedDeclaration): void
    {
        $this->writeScratchFile('classes.php', <<<PHP
            <?php

            namespace DelOlmo\ClassFinder\Tests\Fixtures;

            {$skippedDeclaration}

            final class ValidClass
            {
            }
            PHP);

        $classes = new ClassFinder()->findAll($this->scratch);

        Assert::assertSame([ValidClass::class], $classes);
    }

    /** @return iterable<string, array{non-empty-string}> */
    public static function skippedDeclarationProvider(): iterable
    {
        yield 'a class that does not autoload' => ['final class DoesNotExist {}'];

        yield 'a class that is not instantiable' => ['abstract class BaseClass {}'];
    }

    /**
     * @param class-string       $className
     * @param list<class-string> $expected
     */
    #[DataProvider('subclassProvider')]
    #[TestDox('findByClassName() returns the subclasses and implementations of $className')]
    public function testFindByClassNameReturnsSubclassesAndImplementations(string $className, array $expected): void
    {
        $classes = new ClassFinder(new Finder()->sortByName())->findByClassName($className, self::FIXTURES);

        Assert::assertSame($expected, $classes);
    }

    /** @return iterable<string, array{class-string, list<class-string>}> */
    public static function subclassProvider(): iterable
    {
        yield 'abstract parent' => [BaseClass::class, [ExtendsBaseClass::class]];

        yield 'interface' => [
            TestContract::class,
            [ExtendsBaseClass::class, ImplementsContract::class, TwoClassesSibling::class],
        ];

        yield 'unrelated type' => [DateTimeInterface::class, []];

        yield 'the class itself' => [ValidClass::class, []];
    }

    private function writeScratchFile(string $name, string $contents): string
    {
        $path = $this->scratch . '/' . $name;

        file_put_contents($path, $contents);

        $this->scratchFiles[] = $path;

        return $path;
    }
}
