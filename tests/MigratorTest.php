<?php

use Mockery as m;

class MigratorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMigrationAreRunUpWhenOutstandingMigrationsExist()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			m::mock('Illuminate\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/2_bar.php',
			__DIR__.'/1_foo.php',
			__DIR__.'/3_baz.php',
		));
		$migrator->getRepository()->shouldReceive('getRan')->once()->with('application')->andReturn(array(
			'1_foo',
		));
		$migrator->getRepository()->shouldReceive('getNextBatchNumber')->once()->andReturn(1);
		$migrator->getRepository()->shouldReceive('log')->once()->with('application', '2_bar', 1);
		$migrator->getRepository()->shouldReceive('log')->once()->with('application', '3_baz', 1);
		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('up')->once();
		$bazMock = m::mock('stdClass');
		$bazMock->shouldReceive('up')->once();
		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('2_bar'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('3_baz'))->will($this->returnValue($bazMock));

		$output = m::mock('Symfony\Component\Console\Output\OutputInterface');
		$output->shouldReceive('writeln')->once()->with('<info>Migrated:</info> 2_bar');
		$output->shouldReceive('writeln')->once()->with('<info>Migrated:</info> 3_baz');

		$migrator->runMigrations($output, 'application', __DIR__);
	}


	public function testUpMigrationCanBePretended()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			m::mock('Illuminate\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/2_bar.php',
			__DIR__.'/1_foo.php',
			__DIR__.'/3_baz.php',
		));
		$migrator->getRepository()->shouldReceive('getRan')->once()->with('application')->andReturn(array(
			'1_foo',
		));
		$migrator->getRepository()->shouldReceive('getNextBatchNumber')->once()->andReturn(1);

		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('getConnection')->once()->andReturn(null);
		$barMock->shouldReceive('up')->once();

		$bazMock = m::mock('stdClass');
		$bazMock->shouldReceive('getConnection')->once()->andReturn(null);
		$bazMock->shouldReceive('up')->once();

		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('2_bar'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('3_baz'))->will($this->returnValue($bazMock));

		$connection = m::mock('stdClass');
		$connection->shouldReceive('pretend')->with(m::type('Closure'))->andReturnUsing(function($closure)
		{
			$closure();
			return array('foo');
		},
		function($closure)
		{
			$closure();
			return array('bar');
		});
		$migrator->addConnection('default', function() use ($connection) { return $connection; });

		$output = m::mock('Symfony\Component\Console\Output\OutputInterface');
		$output->shouldReceive('writeln')->once()->with('<info>foo</info>');
		$output->shouldReceive('writeln')->once()->with('<info>bar</info>');

		$migrator->runMigrations($output, 'application', __DIR__, true);	
	}


	public function testNothingIsDoneWhenNoMigrationsAreOutstanding()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			m::mock('Illuminate\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/1_foo.php',
		));
		$migrator->getRepository()->shouldReceive('getRan')->once()->with('application')->andReturn(array(
			'1_foo',
		));

		$output = m::mock('Symfony\Component\Console\Output\OutputInterface');
		$output->shouldReceive('writeln')->once()->with('<info>Nothing to migrate.</info>');

		$migrator->runMigrations($output, 'application', __DIR__);
	}


	public function testLastBatchOfMigrationsCanBeRolledBack()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			m::mock('Illuminate\Filesystem'),
		));
		$migrator->getRepository()->shouldReceive('getLast')->once()->andReturn(array(
			$fooMigration = new MigratorTestMigrationStub('foo'),
			$barMigration = new MigratorTestMigrationStub('bar'),
		));

		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('down')->once();

		$fooMock = m::mock('stdClass');
		$fooMock->shouldReceive('down')->once();

		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('bar'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('foo'))->will($this->returnValue($fooMock));

		$migrator->getRepository()->shouldReceive('delete')->once()->with($barMigration);
		$migrator->getRepository()->shouldReceive('delete')->once()->with($fooMigration);

		$output = m::mock('Symfony\Component\Console\Output\OutputInterface');
		$output->shouldReceive('writeln')->once()->with('<info>Rolled back:</info> bar');
		$output->shouldReceive('writeln')->once()->with('<info>Rolled back:</info> foo');

		$migrator->rollbackMigrations($output);
	}


	public function testRollbackMigrationsCanBePretended()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			m::mock('Illuminate\Filesystem'),
		));
		$migrator->getRepository()->shouldReceive('getLast')->once()->andReturn(array(
			$fooMigration = new MigratorTestMigrationStub('foo'),
			$barMigration = new MigratorTestMigrationStub('bar'),
		));

		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('getConnection')->once()->andReturn(null);
		$barMock->shouldReceive('down')->once();

		$fooMock = m::mock('stdClass');
		$fooMock->shouldReceive('getConnection')->once()->andReturn(null);
		$fooMock->shouldReceive('down')->once();

		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('bar'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('foo'))->will($this->returnValue($fooMock));

		$connection = m::mock('stdClass');
		$connection->shouldReceive('pretend')->with(m::type('Closure'))->andReturnUsing(function($closure)
		{
			$closure();
			return array('bar');
		},
		function($closure)
		{
			$closure();
			return array('foo');
		});
		$migrator->addConnection('default', function() use ($connection) { return $connection; });

		$output = m::mock('Symfony\Component\Console\Output\OutputInterface');
		$output->shouldReceive('writeln')->once()->with('<info>bar</info>');
		$output->shouldReceive('writeln')->once()->with('<info>foo</info>');

		$migrator->rollbackMigrations($output, true);
	}

}


class MigratorTestMigrationStub {
	public function __construct($migration) { $this->migration = $migration; }
	public $migration;
}