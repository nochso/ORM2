<?php
namespace nochso\ORM\Test;

use nochso\ORM\Extract;
use nochso\ORM\Test\Model\User;

class ExtractTest extends \PHPUnit_Framework_TestCase
{
    public function testGetObjectVars()
    {
        $user = new User();
        $data = Extract::getObjectVars($user);

        $public = [
            'id',
            'name',
            'role_id',
            'comments',
            'role',
        ];
        foreach ($public as $name) {
            $this->assertArrayHasKey($name, $data, 'Must return all public properties');
        }

        $nonPublic = [
            '_tableName',
            '_relations',
            'isNew',
        ];
        foreach ($nonPublic as $name) {
            $this->assertArrayNotHasKey($name, $data, 'Must not return protected or private properties');
        }
    }
}
