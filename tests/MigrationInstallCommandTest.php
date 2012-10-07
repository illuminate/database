<?php

use Mockery as m;

class MigrationInstallCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFireCallsRepositoryToInstall()
	{
		$command = new Illuminate\Database\Console\Migrations\InstallCommand($repo = m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'));
		$repo->shouldReceive('createRepository')->once();

		$this->runCommand($command);
	}


	protected function runCommand($command)
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput(array()), new Symfony\Component\Console\Output\NullOutput);
	}

}