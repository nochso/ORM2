# Change Log
This change log follows the style of [keepachangelog.com](http://keepachangelog.com).
<!-- Added for new features.
   Changed for changes in existing functionality.
Deprecated for once-stable features removed in upcoming releases.
   Removed for deprecated features removed in this release.
     Fixed for any bug fixes.
  Security to invite users to upgrade in case of vulnerabilities. -->

## [Unreleased][unreleased]

## [1.3.5] - 2015-11-04
### Fixed
- Fix `Relation::__call` not returning return value

## [1.3.4] - 2015-08-14
### Added
- Add method toArray() in ResultSet for getting a zero indexed array

## [1.3.3] - 2015-08-10
### Changed
- Bump phpunit from 4.7 to 4.8

### Fixed
- Fix overlap in LogEntry pretty statement with short and long keys

## [1.3.2] - 2015-08-03
### Added
- Start keeping a changelog as shown on [keepachangelog.com](http://keepachangelog.com)

### Fixed
- Forgot to put MIT license in composer.json

### Removed
- Remove unnecessary assertion of exception code

## [1.3.1] - 2015-06-14
### Changed
- Release under MIT license
- Publish on [packagist](https://packagist.org/packages/nochso/orm)

### Removed
- Remove unused composer packages Carbon, faker and php-ref

## [1.3.0] - 2015-06-14
### Added
- Add Travis build status to readme

### Changed
- Improve pretty statement
- Formatted code with php-cs-fixer and PSR2 settings
- Use default composer path "vendor" instead of "lib"
- Rename folder test to tests
- Improve phpdoc comments
- count() on models now always returns int
- Use long array syntax

## Fixed
- Fix models fetched by oneSql() being marked as new
- Fix name spaces in tests, 100% pass but needs more coverage
- Fixed emptying of primary key when saving new Model with set primary key

[unreleased]: https://github.com/nochso/ORM2/compare/1.3.5...HEAD
[1.3.5]: https://github.com/nochso/ORM2/compare/1.3.4...1.3.5
[1.3.4]: https://github.com/nochso/ORM2/compare/1.3.3...1.3.4
[1.3.3]: https://github.com/nochso/ORM2/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/nochso/ORM2/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/nochso/ORM2/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/nochso/ORM2/compare/1.2.0...1.3.0