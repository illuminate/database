<?php

use Mockery as m;

class ConnectionTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSettingDefaultCallsGetDefaultGrammar()
	{
		$connection = $this->getMock('Illuminate\Database\Connection', array('getDefaultQueryGrammar'), array(new MockPDO));
		$connection->expects($this->once())->method('getDefaultQueryGrammar')->will($this->returnValue('foo'));
		$connection->useDefaultQueryGrammar();
		$this->assertEquals('foo', $connection->getQueryGrammar());
	}

}

class MockPDO extends PDO { public function __construct() {} }