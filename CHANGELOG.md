# Changelog

All notable changes to this project are documented in this file. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [2.1.1] - 2026-10-27

### Changed

- Same code as 2.1.0, republished from a history without the `.claude/` directory. 2.1.0 is withdrawn: its tag was moved after Packagist published it, and Packagist keeps published versions immutable.
- `.claude/` is ignored.

## [2.1.0] - 2026-10-27 [YANKED]

### Security

- Require PHP 8.4.27 or later on 8.4 and 8.5.12 or later on 8.5, the releases fixing CVE-2026-109618 and CVE-2026-109619.

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

[Unreleased]: https://github.com/delolmo/class-finder/compare/2.1.1...HEAD
[2.1.1]: https://github.com/delolmo/class-finder/compare/2.0...2.1.1
[2.0]: https://github.com/delolmo/class-finder/releases/tag/2.0
