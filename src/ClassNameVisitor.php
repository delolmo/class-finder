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

class ClassNameVisitor extends NodeVisitorAbstract
{
    private string|null $namespace = null;

    private string|null $className = null;

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
        }

        return null;
    }

    public function getClassName(): string
    {
        $namespace = $this->namespace ?? '';

        $className = $this->className ?? '';

        return trim($namespace . '\\' . $className, '\\');
    }
}
