## Illuminate Database

The Illuminate Database component is a full database toolkit for PHP, providing an expressive query builder, ActiveRecord style ORM, and schema builder. It currently supports MySQL, Postgres, SQL Server, and SQLite. It also serves as the database layer of the Laravel PHP framework.

### Usage Instructions

First, create a new "Capsule" manager instance. Capsule aims to make configuring the library for usage outside of the Laravel framework as easy as possible.

```
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
	'driver'    => 'mysql',
	'host'      => 'localhost',
	'database'  => 'database',
	'username'  => 'root',
	'password'  => 'password',
	'charset'   => 'utf8',
	'collation' => 'utf8_unicode_ci',
	'prefix'    => '',
]);

// Setup the Eloquent ORM... (optional)
$capsule->bootEloquent();

// Set the event dispatcher used by Eloquent models... (optional)
$capsule->setEventDispatcher(...);

// Set the cache manager instance used by connections... (optional)
$capsule->setCacheManager(...);

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();
```

Once the Capsule instance has been registered. You may use it like so:

**Using The Query Builder**

```
$users = Capsule::table('users')->where('votes', '>', 100)->get();
```

**Using The Schema Builder**

```
Capsule::schema()->create('users', function($table)
{
	$table->increments('id');
	$table->string('email')->unique();
	$table->timestamps();
});
```

**Using The Eloquent ORM**

```
class User extends Illuminate\Database\Eloquent\Model {}

$users = User::where('votes', '>', 1)->get();
```

For further documentation on using the various database facilities this library provides, consult the [Laravel framework documentation](http://laravel.com/docs).
