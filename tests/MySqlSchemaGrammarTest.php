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