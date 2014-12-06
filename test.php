<pre>
    <?php
    require 'lib/autoload.php';

    use ORM\DBA\DBA;
    use ORM\Model;
    use Test\Model\User;
    use Test\Model\Comment;
    use Test\Model\UserRole;

error_reporting(E_ALL);
    $tt = microtime(true);

    ref::config('showMethods', false);
    ref::config('showPrivateMembers', true);
    ref::config('showStringMatches', false);

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


    $adminRole = new UserRole();
    $adminRole->id = 1;
    $adminRole->description = 'Admin';
    $adminRole->save();

    $userRole = new UserRole();
    $userRole->id = 99;
    $userRole->description = 'User';
    $userRole->save();

    $user = new User();
    $user->name = "John Doe";
    $user->role_id = $userRole->id;
    $user->save();

    for ($i = 0; $i < 3; $i++) {
        $comment = new Comment();
        $comment->user_id = $user->id;
        $comment->comment = "blah blah $i";
        $comment->save();
    }


// $user->fetchRelations();
// echo $user->name . '<br>';
// print_r($user->role);
    $users = User::select()->all()->fetchRelations();
// $u = $users[1];
// echo $u->role->description . "\n";
// $u->role->description = 'fjsdfid';
// $u->role->save();

    foreach ($users as $user) {
        echo $user->name . "\n";
        foreach ($user->comments as $comment) {
            echo "wrote " . $comment->comment . "\n";
        }
    }

// echo count($u->comments);
// print_r($users);
// $roles = UserRole::select()->all();
// r($roles);
// print_r($u->comments[1]);
// print_r($users);
// dump_r($user);
// $users = User::select()->all();
// r($users);
// CREATE TABLE `comment` (
    // `id` int(11) NOT NULL AUTO_INCREMENT,
    // `user_id` int(11) NOT NULL,
    // `comment` varchar(255) NOT NULL,
    // PRIMARY KEY (`id`),
    // KEY `user_id` (`user_id`)
// ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
// CREATE TABLE `user` (
    // `id` int(11) NOT NULL AUTO_INCREMENT,
    // `name` varchar(255) NOT NULL,
    // `user_role_id` int(10) unsigned NOT NULL,
    // PRIMARY KEY (`id`)
// ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
// print_r($statement);


    exit;
    DBA::connect('mysql:host=127.0.0.1;dbname=test', 'root', '');
    setup();

    function setup() {
        User::select()->delete();
        Comment::select()->delete();
        UserRole::select()->delete();

        $role = new UserRole();
        $role->id = 1;
        $role->description = 'Admin';
        $role->save();
        $role = new UserRole();
        $role->id = 2;
        $role->description = 'Moderator';
        $role->save();

        $role = new UserRole();
        $role->id = 3;
        $role->description = 'User';
        $role->save();

        $user = new User();
        $user->name = 'Troy';
        $user->user_role_id = 3;
        $user->save();

        $user = new User();
        $user->name = 'Abed';
        $user->user_role_id = 3;
        $user->save();

        $user = new User();
        $user->name = 'Dean';
        $user->user_role_id = 1;
        $user->save();

        $user = new User();
        $user->name = 'Shirley';
        $user->user_role_id = 2;
        $user->save();

        $users = User::select()->all();
        $i = 0;
        foreach ($users as $user) {
            for ($x = 0; $x < 2; $x++) {
                $comment = new Comment();
                $comment->user_id = $user->id;
                $comment->comment = $user->name . ' blah blah blah ' . ++$i;
                $comment->save();
            }
        }

        // DBA::execute('DELETE FROM user');
        // DBA::execute('DELETE FROM comment');
        // DBA::execute('DELETE FROM user_role');
        // DBA::execute("INSERT INTO user_role (id, description) VALUES (1, 'Admin'), (2, 'User')");
        // for ($i = 1; $i <= 3; $i++) {
        // $data = array(':id' => $i, ':name' => 'test' . $i, ':user_role_id' => 1);
        // if ($i > 1) {
        // $data[':user_role_id'] = 2;
        // }
        // DBA::execute('INSERT INTO user (id, name, user_role_id) VALUES (:id, :name, :user_role_id)', $data);
        // for ($x = 1; $x <= 10; $x++) {
        // $c = new ORM\Comment();
        // $c->user_id = $i;
        // $c->comment = md5($i.$x);
        // $c->save();
        // }
        // }
    }

// DBA::execute('INSERT INTO user (name) VALUES (:name)', array(':name' => 'test2'));
// $s = DBA::execute('SELECT * FROM user');
// while ($row = $s->fetch(PDO::FETCH_OBJ)) {
    // r($row);
// }
// $u = ORM\User::select()
    // ->where('id', 'test')
    // ->neq('id', 1)
    // ->limit(2)
    // ->offset(5)
    // ->orderAsc('id')->orderDesc('name')
    // ->one();
// $users = ORM\User::select()->all();
// $users->update(array('name' => 'testdfdfd'));
// $u = ORM\User::select()->where('id', 2)->one();
// echo '<hr>User<br>';
// r($u);
// $u->fetchRelations();
// r($u);
// exit;
    $users = User::select()->all();
    $users->fetchRelations();
    r($users);

// $role = Test\UserRole::select()->eq('description', 'Admin')->one();
// $role->user->fetch();
// r($role);
// r($users[1]->comments->get());
// $u = ORM\User::select()->limit(1)->all();
// $u->fetchRelations();
// r($u[1]->comments->get());
// $comments = $u->comments->get();
// $comments->update(array('comment' => 'herpderp'));
// r($u);
// r($comments);
// exit;


    /* echo '<hr>6th comment<br>';
      $c = $comments[5];
      r($c);

      echo '<hr>bring back user again<br>';
      $u2 = $c->user->get();
      r($u2); */

// $c = ORM\Comment::select()->one();
// r($c);
// r($users);
// ORM\User::select()->eq('id', '3')->update(array('name' => 'replaced2'));
// r(ORM\User::select()->count());
// $u = ORM\User::select('MIN(id), MAX(id)')->one();
// r($u);
// echo $u->{"MAX(id)"};
// foreach($users as $u) {
    // $u->name=$u->name.'.';
    // $u->save();
// }
// $users[0]->name = "foobar";
// $users[0]->save();
// $u = new ORM\User();
// $u->name = 'sifjsifj';
// $u->save();
// $u = new ORM\User();
// $u->limit(5)->delete();
// $users = ORM\User::select()->all();
// r($users);
// $u = $users[0];
// r($u);
// $u->delete();
// foreach ($users as $user) {
    // r($user);
// }
// r($users);
// echo count($users);
// $users->delete();
// $u = new ORM\User(105);
// r($u);
// $u->delete();


    r(DBA::getLog());

    $tt = round(microtime(1) - $tt, 3);
    ?>
<br><b><?php echo $tt; ?></b></pre>