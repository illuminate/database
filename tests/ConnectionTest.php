<?php

use Mockery as m;

class ConnectionTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSettingDefaultCallsGetDefaultGrammar()
	{
		$connection = $this->getMockConnection();
		$connection->expects($this->once())->method('getDefaultQueryGrammar')->will($this->returnValue('foo'));
		$connection->useDefaultQueryGrammar();
		$this->assertEquals('foo', $connection->getQueryGrammar());
	}


	public function testSettingDefaultCallsGetDefaultPostProcessor()
	{
		$connection = $this->getMockConnection();
		$connection->expects($this->once())->method('getDefaultPostProcessor')->will($this->returnValue('foo'));
		$connection->useDefaultPostProcessor();
		$this->assertEquals('foo', $connection->getPostProcessor());
	}


	public function testSelectOneCallsSelectAndReturnsSingleResult()
	{
		$connection = $this->getMockConnection(array('select'));
		$connection->expects($this->once())->method('select')->with('foo', array('bar' => 'baz'))->will($this->returnValue(array('foo')));
		$this->assertEquals('foo', $connection->selectOne('foo', array('bar' => 'baz')));
	}


	public function testSelectProperlyCallsPDO()
	{
		$pdo = $this->getMock('MockPDO', array('prepare'));
		$statement = $this->getMock('PDOStatement', array('execute', 'fetchAll'));
		$statement->expects($this->once())->method('execute')->with($this->equalTo(array('foo' => 'bar')));
		$statement->expects($this->once())->method('fetchAll')->will($this->returnValue(array('boom')));
		$pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
		$mock = $this->getMockConnection(array(), $pdo);
		$results = $mock->select('foo', array('foo' => 'bar'));
		$this->assertEquals(array('boom'), $results);
		$log = $mock->getQueryLog();
		$this->assertEquals('foo', $log[0]['query']);
		$this->assertEquals(array('foo' => 'bar'), $log[0]['bindings']);
		$this->assertTrue(is_numeric($log[0]['time']));
	}


	public function testInsertCallsTheStatementMethod()
	{
		$connection = $this->getMockConnection(array('statement'));
		$connection->expects($this->once())->method('statement')->with($this->equalTo('foo'), $this->equalTo(array('bar')))->will($this->returnValue('baz'));
		$results = $connection->insert('foo', array('bar'));
		$this->assertEquals('baz', $results);
	}


	public function testUpdateCallsTheAffectingStatementMethod()
	{
		$connection = $this->getMockConnection(array('affectingStatement'));
		$connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(array('bar')))->will($this->returnValue('baz'));
		$results = $connection->update('foo', array('bar'));
		$this->assertEquals('baz', $results);
	}


	public function testDeleteCallsTheAffectingStatementMethod()
	{
		$connection = $this->getMockConnection(array('affectingStatement'));
		$connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(array('bar')))->will($this->returnValue('baz'));
		$results = $connection->delete('foo', array('bar'));
		$this->assertEquals('baz', $results);
	}


	public function testStatementProperlyCallsPDO()
	{
		$pdo = $this->getMock('MockPDO', array('prepare'));
		$statement = $this->getMock('PDOStatement', array('execute'));
		$statement->expects($this->once())->method('execute')->with($this->equalTo(array('bar')))->will($this->returnValue('foo'));
		$pdo->expects($this->once())->method('prepare')->with($this->equalTo('foo'))->will($this->returnValue($statement));
		$mock = $this->getMockConnection(array(), $pdo);
		$results = $mock->statement('foo', array('bar'));
		$this->assertEquals('foo', $results);
		$log = $mock->getQueryLog();
		$this->assertEquals('foo', $log[0]['query']);
		$this->assertEquals(array('bar'), $log[0]['bindings']);
		$this->assertTrue(is_numeric($log[0]['time']));
	}


	public function testAffectingStatementProperlyCallsPDO()
	{
		$pdo = $this->getMock('MockPDO', array('prepare'));
		$statement = $this->getMock('PDOStatement', array('execute', 'rowCount'));
		$statement->expects($this->once())->method('execute')->with($this->equalTo(array('foo' => 'bar')));
		$statement->expects($this->once())->method('rowCount')->will($this->returnValue(array('boom')));
		$pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
		$mock = $this->getMockConnection(array(), $pdo);
		$results = $mock->update('foo', array('foo' => 'bar'));
		$this->assertEquals(array('boom'), $results);
		$log = $mock->getQueryLog();
		$this->assertEquals('foo', $log[0]['query']);
		$this->assertEquals(array('foo' => 'bar'), $log[0]['bindings']);
		$this->assertTrue(is_numeric($log[0]['time']));
	}


	public function testTransactionMethodRunsSuccessfully()
	{
		$pdo = $this->getMock('MockPDO', array('beginTransaction', 'commit'));
		$mock = $this->getMockConnection(array(), $pdo);
		$pdo->expects($this->once())->method('beginTransaction');
		$pdo->expects($this->once())->method('commit');
		$result = $mock->transaction(function($db) { return $db; });
		$this->assertEquals($mock, $result);
	}


	public function testTransactionMethodRollsbackAndThrows()
	{
		$pdo = $this->getMock('MockPDO', array('beginTransaction', 'commit', 'rollBack'));
		$mock = $this->getMockConnection(array(), $pdo);
		$pdo->expects($this->once())->method('beginTransaction');
		$pdo->expects($this->once())->method('rollBack');
		$pdo->expects($this->never())->method('commit');
		try
		{
			$mock->transaction(function() { throw new Exception('foo'); });
		}
		catch (Exception $e)
		{
			$this->assertEquals('foo', $e->getMessage());
		}
	}


	public function testFromCreatesNewQueryBuilder()
	{
		$conn = $this->getMockConnection();
		$conn->setQueryGrammar(m::mock('Illuminate\Database\Query\Grammars\Grammar'));
		$conn->setPostProcessor(m::mock('Illuminate\Database\Query\Processors\Processor'));
		$builder = $conn->table('users');
		$this->assertInstanceOf('Illuminate\Database\Query\Builder', $builder);
		$this->assertEquals('users', $builder->from);
	}


	protected function getMockConnection($methods = array(), $pdo = null)
	{
		$pdo = $pdo ?: new MockPDO;
		$defaults = array('getDefaultQueryGrammar', 'getDefaultPostProcessor');
		return $this->getMock('Illuminate\Database\Connection', array_merge($defaults, $methods), array($pdo));
	}

}

class MockPDO extends PDO { public function __construct() {} }