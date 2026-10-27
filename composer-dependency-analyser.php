<?php

declare(strict_types=1);

use DelOlmo\ClassFinder\Tests\Fixtures\TwoClassesSibling;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration();

$config->disableComposerAutoloadPathScan();

$config->addPathToScan(__DIR__ . '/src', isDev: false);

$config->addPathToScan(__DIR__ . '/tests', isDev: true);

// Declared in the file of another fixture, to pin that every class a file
// declares is found; it exists once that file is loaded, never through PSR-4.
$config->ignoreUnknownClasses([TwoClassesSibling::class]);

return $config;
