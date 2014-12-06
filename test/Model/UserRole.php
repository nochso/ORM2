<?php

namespace Test\Model;

use \ORM\Relation;

class UserRole extends \ORM\Model {

    protected static $_tableName = 'user_role';
    protected static $_relations = array(
        'user' => array(\ORM\Relation::HAS_MANY, 'Test\Model\User', 'id', 'role_id')
    );
    public $id;
    public $description;

}
