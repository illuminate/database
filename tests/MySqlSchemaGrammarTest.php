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


	protected function getConnection()
	{
		return m::mock('Illuminate\Database\Connection');
	}


	public function getGrammar()
	{
		return new Illuminate\Database\Schema\Grammars\MySqlGrammar;
	}

}