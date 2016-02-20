# noch.so / ORM2
[![Packagist](https://img.shields.io/packagist/l/nochso/orm.svg)]()
[![GitHub release](https://img.shields.io/github/release/nochso/orm2.svg)]()
[![Build Status](https://travis-ci.org/nochso/ORM2.svg?branch=master)](https://travis-ci.org/nochso/ORM2)
[![Coveralls](https://img.shields.io/coveralls/nochso/ORM2.svg)]()
[![Dependency Status](https://www.versioneye.com/user/projects/558dc123316338001e00001a/badge.svg?style=flat)](https://www.versioneye.com/user/projects/558dc123316338001e00001a)

A stable ActiveRecord implementation:
 
- fluent query builder
- tested with MySQL and SQLite
- inspired by [Paris](http://j4mie.github.io/idiormandparis/) but with less magic for better autocompletion

The following conventions are used:

- Every table requires a class inheriting from `nochso\ORM\Model`.
- Class names are snaked_cased to table names by default.
    - Otherwise you can override `protected static $_tableName`
- Public properties of model classes correspond to column names.

## Installation
[Get composer](https://getcomposer.org) and require `nochso/orm`.

```
composer require nochso/orm
```

## Example

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
See the [CHANGELOG](CHANGELOG.md) for the full history of changes between releases.
