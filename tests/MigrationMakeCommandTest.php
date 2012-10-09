<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\MakeCommand;

class MigrationMakeCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicCreateGivesCreatorProperArguments()
	{
		$command = new MakeCommand($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), array('application' => __DIR__), __DIR__.'/vendor');
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__, null, false);

		$this->runCommand($command, array('name' => 'create_foo'));
	}


	public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
	{
		$command = new MakeCommand($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), array('application' => __DIR__), __DIR__.'/vendor');
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__, 'users', true);

		$this->runCommand($command, array('name' => 'create_foo', '--table' => 'users', '--create' => true));
	} 


	public function testPackagePathsMayBeUsed()
	{
		$command = new MakeCommand($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), array('application' => __DIR__, 'bar' => __DIR__.'/bar'), __DIR__.'/vendor');
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/bar', null, false);

		$this->runCommand($command, array('name' => 'create_foo', '--package' => 'bar'));
	}


	public function testPackageFallsBackToVendorDirWhenNotExplicit()
	{
		$command = new MakeCommand($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), array('application' => __DIR__), __DIR__.'/vendor');
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/vendor/foo/bar/src/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo', '--package' => 'foo/bar'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
	}

}