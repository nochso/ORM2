<?php
use nochso\ORM\DBA\DBA;
use Test\Model\Dummy;
use Test\Model\User;

//require("lib/autoload.php");
//use nochso\ORM\Model;
//use nochso\ORM\DBA\DBA;
//use Test\Model\User;
//use Test\Model\UserRole;
//use Test\Model\Dummy;

class ModelTest extends PHPUnit_Framework_TestCase
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
        DBA::execute('INSERT INTO user (name, role_id) VALUES(?, ?)', array('Abed', 1));
        DBA::execute('INSERT INTO user (name, role_id) VALUES(?, ?)', array('Dean', 2));
        DBA::execute('INSERT INTO comment (user_id, comment) VALUES(?, ?)', array(1, 'Hi'));
        DBA::execute('INSERT INTO user_role (id, description) VALUES(?, ?)', array(1, 'User'));
        DBA::execute('INSERT INTO user_role (id, description) VALUES(?, ?)', array(2, 'Admin'));
    }

    /**
     * @covers nochso\ORM\Model::__construct
     */
    public function testConstructor()
    {
        // Dummy::$_tableName is null before first instantiation
        $reflection = new \ReflectionProperty('Test\Model\Dummy', '_tableName');
        $reflection->setAccessible(true);
        $this->assertNull($reflection->getValue());

        $dummy = new Dummy();
        $this->assertEquals($reflection->getValue(), 'test_model_dummy');

        // User::$_tableName is defined by User
        $reflection = new \ReflectionProperty('Test\Model\User', '_tableName');
        $reflection->setAccessible(true);
        $this->assertEquals($reflection->getValue(), 'user');

        // Should remain the same after initialisation
        $user = new User();
        $this->assertEquals($reflection->getValue(), 'user');

        // User has relations, so they should have been loaded statically
        $reflection = new \ReflectionProperty('Test\Model\User', '_relations');
        $reflection->setAccessible(true);
        $relations = $reflection->getValue();
        $this->assertArrayHasKey('role', $relations);
        $this->assertArrayHasKey('comments', $relations);

        $user = new User(1);
        $this->assertEquals($user->id, 1);

        $user = new User(99);
        $this->assertNull($user->id);
    }

    /**
     * @covers nochso\ORM\Model::select
     */
    public function testSelect()
    {
        // Should dispense a Model instance
        $user = User::select();
        $this->selectHelper($user);
    }

    /**
     * @covers nochso\ORM\Model::dispense
     */
    public function testDispense()
    {
        $user = new User();

        // Test the non-static alias
        $user2 = $user->dispense();
        $this->selectHelper($user2);
    }

    public function selectHelper($user)
    {
        $this->assertEquals(get_class($user), 'Test\Model\User');

        // Should prepare the query builder with the select column
        $column = 'COUNT(id)';
        $user = User::select($column);

        // Get the private QueryBuilder
        $refQueryBuilder = new \ReflectionProperty('\nochso\ORM\Model', '_queryBuilder');
        $refQueryBuilder->setAccessible(true);
        $queryBuilder = $refQueryBuilder->getValue($user);

        // Test the QueryBuilders selectColumns
        $refSelectColumns = new \ReflectionProperty('\nochso\ORM\QueryBuilder', 'selectColumns');
        $refSelectColumns->setAccessible(true);
        $selectColumns = $refSelectColumns->getValue($queryBuilder);
        $this->assertCount(1, $selectColumns);
        $this->assertEquals($selectColumns[0], $column);
    }

    /**
     * @covers nochso\ORM\Model::save
     */
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

    /**
     * @covers nochso\ORM\Model::save
     */
    public function testSaveChosenPrimaryKey()
    {
        $user = new \Test\Model\Comment();
        $user->id = 99;
        $user->save();
        $this->assertEquals(99, $user->id);
    }

    /**
     * @covers nochso\ORM\Model::save
     * @expectedException Exception
     * @expectedExceptionMessage Can not update existing row of table user without knowing the primary key.
     */
    public function testSaveException()
    {
        $user = User::select()->one();
        unset($user->id);
        $user->save();
    }

    /**
     * @covers nochso\ORM\Model::toAssoc
     */
    public function testToAssoc()
    {
        $user = User::select()->one();

        $assoc = $user->toAssoc();
        $expectedAssoc = array('id' => 1, 'name' => 'Abed', 'role_id' => 1);
        $this->assertEquals($assoc, $expectedAssoc);
    }

    /**
     * @covers nochso\ORM\Model::getPrimaryKeyValue
     */
    public function testGetPrimaryKeyValue()
    {
        $user = new User(1);
        $this->assertEquals($user->getPrimaryKeyValue(), 1);
        $newUser = new User();
        $this->assertNull($newUser->getPrimaryKeyValue());
    }

    /**
     * @covers nochso\ORM\Model::hydrate
     */
    public function testHydrate()
    {
        $user = new User();
        $data = array('id' => 'value', 'name' => 'value2');
        $user->hydrate($data);
        $this->assertEquals('value', $user->id);
        $this->assertEquals('value2', $user->name);
    }

    /**
     * @covers nochso\ORM\Model::one
     */
    public function testOne()
    {
        $user = User::select()->eq('id', 'x')->one();
        $this->assertNull($user);
        $user = User::select()->eq('id', 1)->one();
        $this->assertEquals($user->id, 1);
    }

    /**
     * @covers nochso\ORM\Model::all
     */
    public function testAll()
    {
        $users = User::select()->eq('id', 'x')->all();
        $this->assertInstanceOf('\nochso\ORM\ResultSet', $users);
        $this->assertCount(0, $users);

        $users = User::select()->all();
        $this->assertCount(2, $users);
    }

    /**
     * @covers nochso\ORM\Model::delete
     */
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

    /**
     * @covers nochso\ORM\Model::fetchRelations
     */
    public function testFetchRelations()
    {
        $user = new User(1);
        $this->assertCount(0, $user->comments);
        $this->assertEquals($user->role->id, null);
        $user->fetchRelations();
        $this->assertEquals($user->role->id, 1);
        $this->assertEquals(array('1'), $user->comments->getPrimaryKeyList());
        $this->assertEquals($user->role->description, 'User');
    }

    /**
     * @covers nochso\ORM\Model::update
     */
    public function testUpdate()
    {
        $users = User::select()->all();
        foreach ($users as $user) {
            $this->assertNotEquals($user->name, 'updated name');
        }

        User::select()->update(array('name' => 'updated name'));
        $users = User::select()->all();
        foreach ($users as $user) {
            $this->assertEquals($user->name, 'updated name');
        }
    }
}
