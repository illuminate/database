<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\RollbackCommand;

class MigrationRollbackCommandTest extends PHPUnit_Framework_TestCase {
	
	public function tearDown()
	{
		m::close();
	}


	public function testRollbackCommandCallsMigratorWithProperArguments()
	{
		$command = new RollbackCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
		$migrator->shouldReceive('setConnection')->once()->with(null);
		$migrator->shouldReceive('rollback')->once()->with(m::type('Symfony\Component\Console\Output\OutputInterface'), false);

		$this->runCommand($command);
	}


	public function testRollbackCommandCanBePretended()
	{
		$command = new RollbackCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
		$migrator->shouldReceive('setConnection')->once()->with('foo');
		$migrator->shouldReceive('rollback')->once()->with(m::type('Symfony\Component\Console\Output\OutputInterface'), true);

		$this->runCommand($command, array('--pretend' => true, '--database' => 'foo'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
	}

}