<?php

declare(strict_types=1);

namespace DelOlmo\ClassFinder\Tests\Fixtures;

// Autoloading TwoClasses loads this file, which declares TwoClassesSibling too.
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ClassFileName.NoMatch

final class TwoClasses
{
}

final class TwoClassesSibling implements TestContract
{
}
