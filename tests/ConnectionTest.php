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


	public function testSelectOneCallsSelectAndReturnsSingleResult()
	{
		$connection = $this->getMockConnection(array('select'));
		$connection->expects($this->once())->method('select')->with('foo', array('bar' => 'baz'))->will($this->returnValue(array('foo')));
		$this->assertEquals('foo', $connection->selectOne('foo', array('bar' => 'baz')));
	}


	protected function getMockConnection($methods = array(), $pdo = null)
	{
		$pdo = $pdo ?: new MockPDO;
		return $this->getMock('Illuminate\Database\Connection', array_merge(array('getDefaultQueryGrammar'), $methods), array($pdo));
	}

}

class MockPDO extends PDO { public function __construct() {} }