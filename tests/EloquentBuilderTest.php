<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Builder;

class EloquentBuilderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFindMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('where', 'first'), $this->getMocks());
 		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getKeyName')->once()->andReturn('foo');
		$model->shouldReceive('getTable')->once()->andReturn('table');
		$builder->setModel($model);
		$builder->expects($this->once())->method('where')->with($this->equalTo('foo'), $this->equalTo('='), $this->equalTo('bar'));
		$builder->expects($this->once())->method('first')->with($this->equalTo(array('column')))->will($this->returnValue('baz'));
		$result = $builder->find('bar', array('column'));
		$this->assertEquals('baz', $result);
	}


	protected function getBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammars\Grammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Builder(m::mock('Illuminate\Database\Connection'), $grammar, $processor);
	}


	protected function getMocks()
	{
		$grammar = new Illuminate\Database\Query\Grammars\Grammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		$connection = m::mock('Illuminate\Database\Connection');
		return array($connection, $grammar, $processor);
	}

}