<?php

use Mockery as m;
use Illuminate\Database\Schema\Blueprint;

class PostgresSchemaGrammarTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicCreateTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->create();
		$blueprint->increments('id');
		$blueprint->string('email');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create table "users" ("id" serial primary key not null, "email" varchar(255) not null)', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$blueprint->string('email');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "id" serial primary key not null, add column "email" varchar(255) not null', $statements[0]);
	}


	public function testDropTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->drop();
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop table "users"', $statements[0]);
	}


	public function testDropTableIfExists()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropIfExists();
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop table if exists "users"', $statements[0]);
	}


	public function testDropColumn()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropColumn('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop column "foo"', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->dropColumn(array('foo', 'bar'));
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop column "foo", drop column "bar"', $statements[0]);
	}


	public function testDropColumns()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropColumns('foo', 'bar');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop column "foo", drop column "bar"', $statements[0]);
	}


	public function testDropPrimary()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropPrimary();
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop constraint users_pkey', $statements[0]);
	}


	public function testDropUnique()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropUnique('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop constraint foo', $statements[0]);
	}


	public function testDropIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropIndex('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop index foo', $statements[0]);
	}


	public function testDropForeign()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropForeign('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" drop constraint foo', $statements[0]);
	}


	public function testRenameTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->rename('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" rename to "foo"', $statements[0]);
	}


	public function testAddingPrimaryKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->primary('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add primary key ("foo")', $statements[0]);
	}


	public function testAddingUniqueKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->unique('foo', 'bar');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add constraint bar unique ("foo")', $statements[0]);
	}


	public function testAddingIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->index(array('foo', 'bar'), 'baz');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create index baz on "users" ("foo", "bar")', $statements[0]);
	}


	public function testAddingIncrementingID()
	{
		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "id" serial primary key not null', $statements[0]);
	}


	public function testAddingString()
	{
		$blueprint = new Blueprint('users');
		$blueprint->string('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar(255) not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100);
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar(100) not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100)->nullable()->default('bar');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar(100) null default \'bar\'', $statements[0]);
	}


	public function testAddingText()
	{
		$blueprint = new Blueprint('users');
		$blueprint->text('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" text not null', $statements[0]);
	}


	public function testAddingInteger()
	{
		$blueprint = new Blueprint('users');
		$blueprint->integer('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" integer not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->integer('foo', true);
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" serial primary key not null', $statements[0]);				
	}


	public function testAddingFloat()
	{
		$blueprint = new Blueprint('users');
		$blueprint->float('foo', 5, 2);
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" real not null', $statements[0]);		
	}


	public function testAddingDecimal()
	{
		$blueprint = new Blueprint('users');
		$blueprint->decimal('foo', 5, 2);
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" decimal(5, 2) not null', $statements[0]);
	}


	public function testAddingBoolean()
	{
		$blueprint = new Blueprint('users');
		$blueprint->boolean('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" tinyint not null', $statements[0]);
	}


	public function testAddingEnum()
	{
		$blueprint = new Blueprint('users');
		$blueprint->enum('foo', array('bar', 'baz'));
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" varchar(255) not null', $statements[0]);
	}


	public function testAddingDate()
	{
		$blueprint = new Blueprint('users');
		$blueprint->date('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" date not null', $statements[0]);
	}


	public function testAddingDateTime()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dateTime('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" timestamp not null', $statements[0]);
	}


	public function testAddingTime()
	{
		$blueprint = new Blueprint('users');
		$blueprint->time('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" time not null', $statements[0]);
	}


	public function testAddingTimeStamp()
	{
		$blueprint = new Blueprint('users');
		$blueprint->timestamp('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" timestamp not null', $statements[0]);
	}


	public function testAddingTimeStamps()
	{
		$blueprint = new Blueprint('users');
		$blueprint->timestamps();
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "created_at" timestamp not null, add column "updated_at" timestamp not null', $statements[0]);
	}


	public function testAddingBinary()
	{
		$blueprint = new Blueprint('users');
		$blueprint->binary('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table "users" add column "foo" bytea not null', $statements[0]);
	}


	protected function getConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}


	public function getGrammar()
	{
		return new Illuminate\Database\Schema\Grammars\PostgresGrammar;
	}

}