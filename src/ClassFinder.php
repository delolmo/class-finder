<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

use function class_exists;
use function file_get_contents;

final readonly class ClassFinder
{
    private Finder $finder;

    private Parser $parser;

    public function __construct(
        Finder|null $finder = null,
        Parser|null $parser = null,
    ) {
        $this->finder = $finder ?? new Finder();
        $this->parser = $parser ?? (new ParserFactory())->createForNewestSupportedVersion();
    }

    /** @return list<class-string> */
    public function findAll(string $directory): iterable
    {
        $finder = $this->finder;

        $finder->files()->in($directory)->name('*.php');

        $classes = [];

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();

            if ($filePath === false) {
                continue;
            }

            $fileContents = file_get_contents($filePath);

            if ($fileContents === false) {
                continue;
            }

            $statements = $this->parser->parse($fileContents);

            if ($statements === null) {
                continue;
            }

            $traverser = new NodeTraverser();
            $visitor   = new ClassNameVisitor();
            $traverser->addVisitor($visitor);
            $traverser->traverse($statements);

            $className = $visitor->getClassName();

            if (! class_exists($className)) {
                continue;
            }

            $reflClass = new ReflectionClass($className);

            if (! $reflClass->isInstantiable()) {
                continue;
            }

            /** @var class-string $objectClass */
            $objectClass = $className;

            $classes[] = $objectClass;
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
    public function findByClassName(string $className, string $directory): iterable
    {
        $list = $this->findAll($directory);

        $classes = [];

        foreach ($list as $fileClassName) {
            $reflClass = new ReflectionClass($fileClassName);

            if (! $reflClass->isSubclassOf($className)) {
                continue;
            }

            /** @var class-string<TObject> $objectClassName */
            $objectClassName = $fileClassName;

            $classes[] = $objectClassName;
        }

        return $classes;
    }
}
