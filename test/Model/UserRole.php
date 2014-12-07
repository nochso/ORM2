<?php

namespace Test\Model;

use \nochso\ORM\Relation;

class UserRole extends \nochso\ORM\Model {

    protected static $_tableName = 'user_role';
    protected static $_relations = array(
        'user' => array(\nochso\ORM\Relation::HAS_MANY, 'Test\Model\User', 'id', 'role_id')
    );
    public $id;
    public $description;

}
