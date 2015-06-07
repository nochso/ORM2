<?php

namespace Test\Model;

use \nochso\ORM\Relation;

class Comment extends\nochso \ORM\Model
{

    protected static $_primaryKey = 'cid';
    protected static $_tableName = 'comment';
    protected static $_relations = array(
        'user' => array(Relation::BELONGS_TO, 'Test\Model\User')
    );
    public $cid;
    public $user_id;
    public $comment;
    // Relations
    public $user;
}