<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\ResetCommand;

class MigrationResetCommandTest extends PHPUnit_Framework_TestCase {
	
	public function tearDown()
	{
		m::close();
	}


	public function testResetCommandCallsMigratorWithProperArguments()
	{
		$command = new ResetCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
		$migrator->shouldReceive('setConnection')->once()->with(null);
		$migrator->shouldReceive('rollback')->twice()->with(m::type('Symfony\Component\Console\Output\OutputInterface'), false)->andReturn(true, false);

		$this->runCommand($command);
	}


	public function testResetCommandCanBePretended()
	{
		$command = new ResetCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
		$migrator->shouldReceive('setConnection')->once()->with('foo');
		$migrator->shouldReceive('rollback')->twice()->with(m::type('Symfony\Component\Console\Output\OutputInterface'), true)->andReturn(true, false);

		$this->runCommand($command, array('--pretend' => true, '--database' => 'foo'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
	}

}