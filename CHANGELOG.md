# Changelog

All notable changes to this project are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Security

- Require PHP 8.4.27 or later, the release fixing CVE-2026-109618 and CVE-2026-109619.

### Added

- `ClassNameVisitor::getClassNames()` lists every named class a file declares, in order.
- Support for `symfony/finder` 8.

### Changed

- `findAll()` and `findByClassName()` declare `array` as their return type instead of `iterable`; they already returned lists.
- `findAll()` finds every class a file declares, not only the last one.
- `findAll()` skips files that cannot be read or do not parse instead of emitting a warning or throwing `PhpParser\Error`.
- The `Finder` given to the constructor is a template: every search runs on a copy of it, keeping its filters.
- `nikic/php-parser` 5.9 or later is required; 4.x was allowed but never worked, since the parser is built with the 5.x API.

### Fixed

- Calling `findAll()` or `findByClassName()` twice on one instance searched the directories of every earlier call as well.

## [2.0] - 2025-11-18

- Last release before this changelog.

[Unreleased]: https://github.com/delolmo/class-finder/compare/2.0...HEAD
[2.0]: https://github.com/delolmo/class-finder/releases/tag/2.0
