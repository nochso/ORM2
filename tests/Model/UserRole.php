<?php

namespace nochso\ORM\Test\Model;

use nochso\ORM\Model;
use nochso\ORM\Relation;

class UserRole extends Model
{
    protected static $_tableName = 'user_role';
    protected static $_relations = [
        'user' => [Relation::HAS_MANY, User::class, 'id', 'role_id'],
    ];
    public $id;
    public $description;
}
