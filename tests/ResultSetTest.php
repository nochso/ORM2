<?php

use nochso\ORM\DBA\DBA;
use nochso\ORM\ResultSet;
use Test\Model\User;

class ResultSetTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        DBA::connect('sqlite::memory:', '', '');
        DBA::execute('CREATE TABLE comment (
		  id INTEGER PRIMARY KEY AUTOINCREMENT,
		  user_id INTEGER,
		  comment TEXT
		)');
        DBA::execute('CREATE TABLE user (
		  id INTEGER PRIMARY KEY AUTOINCREMENT,
		  name TEXT,
		  role_id INTEGER
		)');
        DBA::execute('CREATE TABLE user_role (
		  id INTEGER PRIMARY KEY AUTOINCREMENT,
		  description TEXT
		);');
    }

    public function setUp()
    {
        DBA::execute('DELETE FROM user');
        DBA::execute("DELETE FROM sqlite_sequence WHERE name = 'user'");
        DBA::execute('DELETE FROM user_role');
        DBA::execute('INSERT INTO user (id, name, role_id) VALUES(?, ?, ?)', array(1, 'Abed', 1));
        DBA::execute('INSERT INTO user (id, name, role_id) VALUES(?, ?, ?)', array(2, 'Dean', 99));
        DBA::execute('INSERT INTO comment (user_id, comment) VALUES (?, ?)', array(1, 'text'));
        DBA::execute('INSERT INTO comment (user_id, comment) VALUES (?, ?)', array(1, 'text2'));
        DBA::execute('INSERT INTO user_role (id, description) VALUES (?, ?)', array(1, 'User'));
        DBA::execute('INSERT INTO user_role (id, description) VALUES (?, ?)', array(99, 'Administrator'));
    }

    /**
     * @covers nochso\ORM\ResultSet::fetchRelations
     */
    public function testFetchRelations()
    {
        $set = User::select()->all();
        foreach ($set as $user) {
            $this->assertNull($user->role->data);
            $this->assertNull($user->comments->data);
        }
        $set->fetchRelations();
        foreach ($set as $user) {
            $this->assertNotNull($user->role->data);
        }
        $this->assertEquals($set[1]->role->description, 'User');
        $this->assertEquals($set[2]->role->description, 'Administrator');

        $this->assertCount(2, $set[1]->comments);
        $this->assertCount(0, $set[2]->comments);

        $empty = User::select()->gt('id', 100)->all();
        $empty->fetchRelations();
    }

    /**
     * @covers nochso\ORM\ResultSet::getPrimaryKeyList
     */
    public function testGetPrimaryKeyList()
    {
        $users = User::select()->all();
        $keyList = $users->getPrimaryKeyList();
        $this->assertEquals($keyList, array(1, 2));

        $users = User::select()->eq('id', 'x')->all();
        $keyList = $users->getPrimaryKeyList();
        $this->assertTrue(is_array($keyList));
        $this->assertCount(0, $keyList);
    }

    /**
     * @covers nochso\ORM\ResultSet::update
     */
    public function testUpdate()
    {
        $users = User::select()->all();
        $users->update(array('role_id' => 3));

        $users = User::select()->all();
        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertEquals($user->role_id, 3);
        }

        $users = User::select()->eq('id', 99)->all();
        $users->update(array('role_id' => 4));
        $users = User::select()->eq('role_id', 4)->all();
        $this->assertCount(0, $users);
    }

    /**
     * @covers nochso\ORM\ResultSet::save
     */
    public function testSave()
    {
        $users = User::select()->all();
        // Change the users
        foreach ($users as $user) {
            $user->name = "id" . $user->id;
            $user->role_id = $user->id + 5;
        }

        // Before saving, insert a new user that should not be affected by save()
        DBA::execute('INSERT INTO user (id, name, role_id) VALUES(?, ?, ?)', array(3, 'odd man out', 1));
        $users->save();

        // Make sure our changes were made, excluding the odd man out
        $users = User::select()->all();
        foreach ($users as $user) {
            if ($user->id == 3) {
                $this->assertEquals('odd man out', $user->name);
                $this->assertEquals(1, $user->role_id);
            } else {
                $this->assertEquals("id" . $user->id, $user->name);
                $this->assertEquals($user->id + 5, $user->role_id);
            }
        }

        $users = User::select()->eq('id', 99)->all();
        $users->save();
    }

    /**
     * @covers nochso\ORM\ResultSet::offsetGet
     */
    public function testOffsetGet()
    {
        $ids = array(1, 2);
        $users = User::select()->all();
        foreach ($ids as $id) {
            $this->assertArrayHasKey($id, $users);
            $this->assertEquals($users[$id]->id, $id);
        }
        $this->assertNull($users[55]);
    }

    /**
     * @covers nochso\ORM\ResultSet::offsetSet
     */
    public function testOffsetSet()
    {
        $users = User::select()->eq('id', 99)->all();
        $this->assertArrayNotHasKey(1, $users);
        $user = new User(1);
        $users[1] = $user;
        $this->assertArrayHasKey(1, $users);

        $users[] = $user;
        $this->assertArrayHasKey(2, $users);
    }

    /**
     * @covers nochso\ORM\ResultSet::delete
     */
    public function testDelete()
    {
        // Create unique user
        $user = new User();
        $user->name = 'odd man out';
        $user->role_id = 1;
        $user->save();

        // Delete every user but our new one
        $users = User::select()->neq('name', 'odd man out')->all();
        $users->delete();

        // Assert only our user is left
        $users = User::select()->all();
        $this->assertCount(1, $users);
        $user = $users->current();
        $this->assertEquals($user->name, 'odd man out');

        // Delete on emtpy ResultSet
        $users = User::select()->eq('name', 'nobody')->all();
        $this->assertCount(0, $users);
        $users->delete();
        $users = User::select()->all();
        $this->assertCount(1, $users);
    }

    /**
     * @covers nochso\ORM\ResultSet::count
     */
    public function testCount()
    {
        $users = User::select()->all();
        $this->assertEquals(count($users), 2);
    }
}
