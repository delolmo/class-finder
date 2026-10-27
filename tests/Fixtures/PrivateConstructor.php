<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder\Tests\Fixtures;

final class PrivateConstructor
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }
}
