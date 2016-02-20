<?php

namespace Test\Model;

class UserRole extends \nochso\ORM\Model
{
    protected static $_tableName = 'user_role';
    protected static $_relations = [
        'user' => [\nochso\ORM\Relation::HAS_MANY, 'Test\Model\User', 'id', 'role_id'],
    ];
    public $id;
    public $description;
}
