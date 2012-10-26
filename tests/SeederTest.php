<?php

use Mockery as m;
use Illuminate\Database\Seeder;

class SeederTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testTablesAreSeededFromSeedFiles()
	{
		$seeder = new Seeder($files = m::mock('Illuminate\Filesystem'), $events = m::mock('Illuminate\Events\Dispatcher'));
		$files->shouldReceive('glob')->once()->with('path/*.php')->andReturn(array('path/b.php', 'path/a.php'));
		$files->shouldReceive('getRequire')->once()->with('path/a.php')->andReturn(array('table' => 'a_table', array('name' => 'Taylor')));
		$files->shouldReceive('getRequire')->once()->with('path/b.php')->andReturn(array(array('name' => 'Dayle')));
		$connection = m::mock('Illuminate\Database\Connection');
		$connection->shouldReceive('table')->once()->with('a_table')->andReturn($connection);
		$connection->shouldReceive('insert')->once()->with(array(array('name' => 'Taylor')));
		$connection->shouldReceive('table')->once()->with('b')->andReturn($connection);
		$connection->shouldReceive('insert')->once()->with(array(array('name' => 'Dayle')));
		$events->shouldReceive('fire')->once()->with('illuminate.seeding', array('a_table', 1));
		$events->shouldReceive('fire')->once()->with('illuminate.seeding', array('b', 1));

		$seeder->seed($connection, 'path');
	}

}