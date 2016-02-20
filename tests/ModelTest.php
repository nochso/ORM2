<?php

namespace nochso\ORM\Test;

use nochso\ORM\DBA\DBA;
use nochso\ORM\Model;
use nochso\ORM\QueryBuilder;
use nochso\ORM\Test\Model\Comment;
use nochso\ORM\Test\Model\Dummy;
use nochso\ORM\Test\Model\User;

class ModelTest extends \PHPUnit_Framework_TestCase
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
        DBA::execute('DELETE FROM user_role');
        DBA::execute('DELETE FROM comment');
        DBA::execute("DELETE FROM sqlite_sequence WHERE name IN ('user', 'comment')");
        DBA::execute('INSERT INTO user (name, role_id) VALUES(?, ?)', ['Abed', 1]);
        DBA::execute('INSERT INTO user (name, role_id) VALUES(?, ?)', ['Dean', 2]);
        DBA::execute('INSERT INTO comment (user_id, comment) VALUES(?, ?)', [1, 'Hi']);
        DBA::execute('INSERT INTO user_role (id, description) VALUES(?, ?)', [1, 'User']);
        DBA::execute('INSERT INTO user_role (id, description) VALUES(?, ?)', [2, 'Admin']);
    }

    public function testConstructor()
    {
        // Dummy::$_tableName is null before first instantiation
        $reflection = new \ReflectionProperty(Dummy::class, '_tableName');
        $reflection->setAccessible(true);
        $this->assertNull($reflection->getValue());

        $dummy = new Dummy();
        $this->assertEquals($reflection->getValue(), 'nochso_orm_test_model_dummy');

        // User::$_tableName is defined by User
        $reflection = new \ReflectionProperty(User::class, '_tableName');
        $reflection->setAccessible(true);
        $this->assertEquals($reflection->getValue(), 'user');

        // Should remain the same after initialisation
        $user = new User();
        $this->assertEquals($reflection->getValue(), 'user');

        // User has relations, so they should have been loaded statically
        $reflection = new \ReflectionProperty(User::class, '_relations');
        $reflection->setAccessible(true);
        $relations = $reflection->getValue();
        $this->assertArrayHasKey('role', $relations);
        $this->assertArrayHasKey('comments', $relations);

        $user = new User(1);
        $this->assertEquals($user->id, 1);

        $user = new User(99);
        $this->assertNull($user->id);
    }

    public function testSelect()
    {
        // Should dispense a Model instance
        $user = User::select();
        $this->selectHelper($user);
    }

    public function testDispense()
    {
        $user = new User();

        // Test the non-static alias
        $user2 = $user->dispense();
        $this->selectHelper($user2);
    }

    public function selectHelper($user)
    {
        $this->assertEquals(get_class($user), User::class);

        // Should prepare the query builder with the select column
        $column = 'COUNT(id)';
        $user = User::select($column);

        // Get the private QueryBuilder
        $refQueryBuilder = new \ReflectionProperty(Model::class, 'queryBuilder');
        $refQueryBuilder->setAccessible(true);
        $queryBuilder = $refQueryBuilder->getValue($user);

        // Test the QueryBuilders selectColumns
        $refSelectColumns = new \ReflectionProperty(QueryBuilder::class, 'selectColumns');
        $refSelectColumns->setAccessible(true);
        $selectColumns = $refSelectColumns->getValue($queryBuilder);
        $this->assertCount(1, $selectColumns);
        $this->assertEquals($selectColumns[0], $column);
    }

    public function testSave()
    {
        // Insert new user, should fill the primary key after insert
        $user = new User();
        $user->name = "save tester";
        $user->role_id = 1;
        $this->assertNull($user->id);
        $user->save();
        $this->assertNotNull(0, $user->id);
        $this->assertGreaterThan(0, $user->id);

        // Change something and test if it has been UPDATEd
        $user->name = "changed tester";
        $user->save();
        $user2 = new User($user->id);
        $this->assertEquals($user2->name, $user->name);
        $this->assertEquals($user2->id, $user->id);
        $this->assertEquals($user2->role_id, $user->role_id);
    }

    public function testSaveChosenPrimaryKey()
    {
        $user = new Comment();
        $user->id = 99;
        $user->save();
        $this->assertEquals(99, $user->id);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not update existing row of table user without knowing the primary key.
     */
    public function testSaveException()
    {
        $user = User::select()->one();
        unset($user->id);
        $user->save();
    }

    public function testToAssoc()
    {
        $user = User::select()->one();

        $assoc = $user->toAssoc();
        $expectedAssoc = ['id' => 1, 'name' => 'Abed', 'role_id' => 1];
        $this->assertEquals($assoc, $expectedAssoc);
    }

    public function testGetPrimaryKeyValue()
    {
        $user = new User(1);
        $this->assertEquals($user->getPrimaryKeyValue(), 1);
        $newUser = new User();
        $this->assertNull($newUser->getPrimaryKeyValue());
    }

    public function testHydrate()
    {
        $user = new User();
        $data = ['id' => 'value', 'name' => 'value2'];
        $user->hydrate($data);
        $this->assertEquals('value', $user->id);
        $this->assertEquals('value2', $user->name);
    }

    public function testOne()
    {
        $user = User::select()->eq('id', 'x')->one();
        $this->assertNull($user);
        $user = User::select()->eq('id', 1)->one();
        $this->assertEquals($user->id, 1);
    }

    public function testAll()
    {
        $users = User::select()->eq('id', 'x')->all();
        $this->assertInstanceOf('\nochso\ORM\ResultSet', $users);
        $this->assertCount(0, $users);

        $users = User::select()->all();
        $this->assertCount(2, $users);
    }

    public function testDelete()
    {
        // Delete user by filtering
        User::select()->eq('id', 2)->delete();
        $user = new User(2);
        $this->assertNull($user->id);

        // Delete specific User instance
        $user = new User(1);
        $user->delete();
        $user = new User(1);
        $this->assertNull($user->id);

        // Cover late creation of query builder
        $user = new User();
        $user->id = 1;
        $user->delete();
    }

    public function testFetchRelations()
    {
        $user = new User(1);
        $this->assertCount(0, $user->comments);
        $this->assertEquals($user->role->id, null);
        $user->fetchRelations();
        $this->assertEquals($user->role->id, 1);
        $this->assertEquals(['1'], $user->comments->getPrimaryKeyList());
        $this->assertEquals($user->role->description, 'User');
    }

    public function testUpdate()
    {
        $users = User::select()->all();
        foreach ($users as $user) {
            $this->assertNotEquals($user->name, 'updated name');
        }

        User::select()->update(['name' => 'updated name']);
        $users = User::select()->all();
        foreach ($users as $user) {
            $this->assertEquals($user->name, 'updated name');
        }
    }
}
