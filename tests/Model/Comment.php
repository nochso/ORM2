<?php

namespace nochso\ORM\Test\Model;

use nochso\ORM\Model;
use nochso\ORM\Relation;

class Comment extends Model
{
    protected static $_primaryKey = 'id';
    protected static $_tableName = 'comment';
    protected static $_relations = [
        'user' => [Relation::BELONGS_TO, User::class],
    ];
    public $id;
    public $user_id;
    public $comment;
    // Relations
    public $user;
}
