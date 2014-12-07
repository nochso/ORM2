# noch.so / ORM2

## Install via composer
composer.json without packagist:
```javascript
{
  "repositories": [{
    "type": "vcs",
    "url":  "https://github.com/nochso/ORM2.git"
  }],
    "require": {
  		"nochso/ORM": "@dev"
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
echo $john->name; // 'john doe'

// Change and save his name
$john->name = 'herbert';
$john->save();

// Afterwards $john->subscriptions contains a list of \TV\Model\Subscription instances
$john->subscriptions->fetch();

if (count($john->subscriptions) > 0) {
  $john->subscriptions[0]->delete();
}
```
