<?php

use Mockery as m;

class ConnectionFactoryTest extends PHPUnit_Framework_TestCase {

	public function testMakeCallsCreateConnection()
	{
		$factory = $this->getMock('Illuminate\Database\Connectors\ConnectionFactory', array('createConnector'));
		$connector = m::mock('stdClass');
		$connector->shouldReceive('connect')->once()->with(array('config'))->andReturn('foo');
		$factory->expects($this->once())->method('createConnector')->with(array('config'))->will($this->returnValue($connector));
		$connection = $factory->make(array('config'));

		$this->assertEquals('foo', $connection);
	}


	public function testProperInstancesAreReturnedForProperDrivers()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory;
		$this->assertInstanceOf('Illuminate\Database\Connectors\MySqlConnector', $factory->createConnector(array('driver' => 'mysql')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\PostgresConnector', $factory->createConnector(array('driver' => 'pgsql')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\SQLiteConnector', $factory->createConnector(array('driver' => 'sqlite')));
		$this->assertInstanceOf('Illuminate\Database\Connectors\SqlServerConnector', $factory->createConnector(array('driver' => 'sqlsrv')));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testIfDriverIsntSetExceptionIsThrown()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory;
		$factory->createConnector(array('foo'));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionIsThrownOnUnsupportedDriver()
	{
		$factory = new Illuminate\Database\Connectors\ConnectionFactory;
		$factory->createConnector(array('driver' => 'foo'));
	}

}