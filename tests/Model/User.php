<?php

namespace nochso\ORM\Test\Model;

use nochso\ORM\Model;
use nochso\ORM\Relation;

class User extends Model
{
    protected static $_tableName = 'user';
    protected static $_relations = [
        'comments' => [Relation::HAS_MANY, Comment::class],
        'role' => [Relation::HAS_ONE, UserRole::class, 'role_id', 'id'],
    ];
    public $id;
    public $name;
    public $role_id;
    // Relations
    public $comments;
    public $role;
}
