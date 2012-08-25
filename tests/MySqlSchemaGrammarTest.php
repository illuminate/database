<?php

use Mockery as m;
use Illuminate\Database\Schema\Blueprint;

class MySqlSchemaGrammarTest extends PHPUnit_Framework_TestCase {

	public function testBasicCreateTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->create();
		$blueprint->increments('id');
		$blueprint->string('email');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('create table `users` (`id` int not null auto_increment primary key, `email` varchar(255) not null)', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$blueprint->string('email');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `id` int not null auto_increment primary key, add `email` varchar(255) not null', $statements[0]);
	}


	public function testDropTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->drop();
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('drop table `users`', $statements[0]);
	}


	public function testDropColumn()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropColumn('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop `foo`', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->dropColumn(array('foo', 'bar'));
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop `foo`, drop `bar`', $statements[0]);
	}


	public function testDropColumns()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropColumns('foo', 'bar');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop `foo`, drop `bar`', $statements[0]);
	}


	public function testDropPrimary()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropPrimary();
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop primary key', $statements[0]);
	}


	public function testDropUnique()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropUnique('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop index foo', $statements[0]);
	}


	public function testDropIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropIndex('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop index foo', $statements[0]);
	}


	public function testDropForeign()
	{
		$blueprint = new Blueprint('users');
		$blueprint->dropForeign('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` drop foreign key foo', $statements[0]);
	}


	public function testRenameTable()
	{
		$blueprint = new Blueprint('users');
		$blueprint->rename('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('rename table `users` to `foo`', $statements[0]);
	}


	public function testAddingPrimaryKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->primary('foo', 'bar');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add primary key bar(`foo`)', $statements[0]);
	}


	public function testAddingUniqueKey()
	{
		$blueprint = new Blueprint('users');
		$blueprint->unique('foo', 'bar');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add unique bar(`foo`)', $statements[0]);
	}


	public function testAddingIndex()
	{
		$blueprint = new Blueprint('users');
		$blueprint->index(array('foo', 'bar'), 'baz');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add index baz(`foo`, `bar`)', $statements[0]);
	}


	public function testAddingIncrementingID()
	{
		$blueprint = new Blueprint('users');
		$blueprint->increments('id');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `id` int not null auto_increment primary key', $statements[0]);
	}


	public function testAddingString()
	{
		$blueprint = new Blueprint('users');
		$blueprint->string('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `foo` varchar(255) not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100);
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `foo` varchar(100) not null', $statements[0]);

		$blueprint = new Blueprint('users');
		$blueprint->string('foo', 100)->nullable()->default('bar');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `foo` varchar(100) null default \'bar\'', $statements[0]);
	}


	public function testAddingText()
	{
		$blueprint = new Blueprint('users');
		$blueprint->text('foo');
		$statements = $blueprint->toSql($this->getGrammar());

		$this->assertEquals(1, count($statements));
		$this->assertEquals('alter table `users` add `foo` text not null', $statements[0]);
	}


	protected function getConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}


	public function getGrammar()
	{
		return new Illuminate\Database\Schema\Grammars\MySqlGrammar;
	}

}