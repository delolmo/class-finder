<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Finder\Finder;

use function class_exists;
use function is_subclass_of;

/**
 * Lists the instantiable, autoloadable classes declared in the PHP files of a
 * directory. Checking that a class is autoloadable loads its file.
 */
final readonly class ClassFinder
{
    private Finder $finder;

    private Parser $parser;

    /** @param Finder|null $finder A template: every search runs on a fresh copy, keeping its filters. */
    public function __construct(
        Finder|null $finder = null,
        Parser|null $parser = null,
    ) {
        $this->finder = $finder ?? new Finder();
        $this->parser = $parser ?? new ParserFactory()->createForNewestSupportedVersion();
    }

    /**
     * Files that cannot be read or parsed are skipped.
     *
     * @return list<class-string>
     */
    public function findAll(string $directory): array
    {
        // Finder accumulates the directories it is given; a copy keeps one
        // search from leaking into the next.
        $finder = clone $this->finder;

        $finder->files()->in($directory)->name('*.php');

        $classes = [];

        foreach ($finder as $file) {
            try {
                $statements = $this->parser->parse($file->getContents());
            } catch (RuntimeException) {
                // The parser's Error is a RuntimeException too.
                continue;
            }

            if ($statements === null) {
                continue;
            }

            $visitor = new ClassNameVisitor();

            $traverser = new NodeTraverser($visitor);
            $traverser->traverse($statements);

            foreach ($visitor->getClassNames() as $className) {
                if (! class_exists($className)) {
                    continue;
                }

                if (! new ReflectionClass($className)->isInstantiable()) {
                    continue;
                }

                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * @param class-string<TObject> $className
     *
     * @return list<class-string<TObject>>
     *
     * @template TObject of object
     */
    public function findByClassName(string $className, string $directory): array
    {
        $classes = [];

        foreach ($this->findAll($directory) as $fileClassName) {
            if (! is_subclass_of($fileClassName, $className, true)) {
                continue;
            }

            $classes[] = $fileClassName;
        }

        return $classes;
    }
}
