# delolmo/class-finder

[![Packagist Version](https://img.shields.io/packagist/v/delolmo/class-finder.svg?style=flat-square)](https://packagist.org/packages/delolmo/class-finder)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Find the instantiable classes declared in a directory, optionally narrowed to the subclasses or implementations of a given type.

## Requirements

* PHP ^8.4.27

## Installation

```sh
composer require delolmo/class-finder
```

## Usage

```php
use App\Handler\HandlerInterface;
use DelOlmo\ClassFinder\ClassFinder;

$classFinder = new ClassFinder();

// Every instantiable class declared in src/Handler
$classes = $classFinder->findAll(__DIR__ . '/src/Handler');

// Only those that extend or implement HandlerInterface
$handlers = $classFinder->findByClassName(HandlerInterface::class, __DIR__ . '/src/Handler');
```

Both methods return a `list` of class names. `findByClassName()` is typed with a template, so static analysers know `$handlers` is a `list<class-string<HandlerInterface>>`.

### Filtering the files

The constructor takes an optional `Symfony\Component\Finder\Finder` to use as a template. Every search runs on a fresh copy of it, so its filters apply to every call while the directories of one call never leak into the next:

```php
use Symfony\Component\Finder\Finder;

$classFinder = new ClassFinder(
    new Finder()->exclude('Tests')->notName('*Fixture.php'),
);
```

A custom `PhpParser\Parser` can be passed as the second argument.

## What is found

* Every named class a file declares, several per file included.
* Only instantiable classes: abstract classes, classes with a non-public constructor, interfaces, traits and enums are left out.
* Only autoloadable classes. Checking this runs the autoloader, which **loads the file**: scan only directories whose files are safe to include.
* Files that cannot be read or do not parse are skipped.
