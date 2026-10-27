<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder;

use Override;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

use function trim;

/** Collects the fully qualified names of the named classes a file declares. */
final class ClassNameVisitor extends NodeVisitorAbstract
{
    private string|null $namespace = null;

    private string|null $className = null;

    /** @var list<string> */
    private array $classNames = [];

    /** @inheritdoc */
    #[Override]
    public function enterNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->namespace = $node->name?->toString() ?? '';
        }

        if (
            $node instanceof Class_ &&
            $node->name instanceof Identifier
        ) {
            $this->className = $node->name->name;

            $this->classNames[] = $this->qualify($this->className);
        }

        return null;
    }

    /**
     * The last namespace joined to the last class name, whichever namespace
     * that class was declared in. Use getClassNames() for files declaring
     * more than one class.
     */
    public function getClassName(): string
    {
        return $this->qualify($this->className ?? '');
    }

    /** @return list<string> In declaration order. */
    public function getClassNames(): array
    {
        return $this->classNames;
    }

    private function qualify(string $className): string
    {
        return trim(($this->namespace ?? '') . '\\' . $className, '\\');
    }
}
