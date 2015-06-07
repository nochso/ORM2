<?php

namespace Test\Model;

use \nochso\ORM\Relation;

class User extends \nochso\ORM\Model
{
    protected static $_tableName = 'user';
    protected static $_relations = array(
        'comments' => array(Relation::HAS_MANY, 'Test\Model\Comment'),
        'role' => array(Relation::HAS_ONE, 'Test\Model\UserRole', 'role_id', 'id')
    );
    public $id;
    public $name;
    public $role_id;
    // Relations
    public $comments;
    public $role;
}
