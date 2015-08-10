# noch.so / ORM2
[![Build Status](https://travis-ci.org/nochso/ORM2.svg?branch=master)](https://travis-ci.org/nochso/ORM2)
[![Dependency Status](https://www.versioneye.com/user/projects/558dc123316338001e00001a/badge.svg?style=flat)](https://www.versioneye.com/user/projects/558dc123316338001e00001a)

## Install via composer
composer.json without packagist:
```javascript
{
  "repositories": [{
    "type": "vcs",
    "url":  "https://github.com/nochso/ORM2.git"
  }],
    "require": {
  		"nochso/orm": "~1.3"
    }
}
```

## Basic usage
Much like Paris or most fluent/AR packages, except focus on speed and proper hinting of class properties.

```php
use nochso\ORM\Model;
use nochso\ORM\Relation;

class User extends Model {
    /* Actual database table name */
    protected static $_tableName = 'user';
    /* The Subscription class must have a field "user_id" to identify the user's subscriptions */
    protected static $_relations = array(
        'subscriptions' => array(Relation::HAS_MANY, '\TV\Model\Subscription')
    );
    public $id;
    public $name;
    public $password;
    public $token;

    /* Lets you access the relation to the user's subscriptions.
     * Names must match with the key in $_relations */
    public $subscriptions;
}
```
```php
// Fetch a user by his name
$john = User::select()->eq('name', 'john doe')->one();

// or achieve the same using the primary key
$sameJohn = User::select()->one($john->id);

echo $john->name; // 'john doe'

// Change and save his name
$john->name = 'herbert';
$john->save();

// Loads the related list of \TV\Model\Subscription instances as defined in User::$_relations['subscriptions']
$john->subscriptions->fetch();

if (count($john->subscriptions) > 0) {
  $john->subscriptions[0]->delete();
}

// Update certain columns of certain users
User::select()
    ->in('user_id', array(3, 6, 15))
    ->update(array('banned' => 1));
```

# Change log
[keepachangelog.com](http://keepachangelog.com)
<!-- Added for new features.
   Changed for changes in existing functionality.
Deprecated for once-stable features removed in upcoming releases.
   Removed for deprecated features removed in this release.
     Fixed for any bug fixes.
  Security to invite users to upgrade in case of vulnerabilities. -->

## [Unreleased][unreleased]
### Added
- Extension of Model for nested set functionality

### Security
- Proper quoting of identifiers for different SQL dialects

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

[unreleased]: https://github.com/nochso/ORM2/compare/1.3.3...HEAD
[1.3.3]: https://github.com/nochso/ORM2/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/nochso/ORM2/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/nochso/ORM2/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/nochso/ORM2/compare/1.2.0...1.3.0
