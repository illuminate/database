<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentBelongsToTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testUpdateMethodRetreievesModelAndUpdates()
	{
		$relation = $this->getRelation();
		$mock = m::mock('Illuminate\Database\Eloquent\Model');
		$mock->shouldReceive('fill')->once()->with(array('attributes'))->andReturn($mock);
		$mock->shouldReceive('save')->once()->andReturn(true);
		$relation->getQuery()->shouldReceive('first')->once()->andReturn($mock);

		$this->assertTrue($relation->update(array('attributes')));
	}


	public function testFastUpdateJustCallsQueryWithoutRetrieval()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('update')->once()->with(array('attributes'))->andReturn(true);
		$relation->getQuery()->shouldReceive('first')->never();

		$this->assertTrue($relation->fastUpdate(array('attributes')));	
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('id', array('foreign.value', 'foreign.value.two'));
		$models = array(new EloquentBelongsToModelStub, new EloquentBelongsToModelStub, new AnotherEloquentBelongsToModelStub);
		$relation->addEagerConstraints($models);
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	protected function getRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('id', '=', 'foreign.value');
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getKeyName')->andReturn('id');
		$builder->shouldReceive('getModel')->andReturn($model);
		$parent = new EloquentBelongsToModelStub;
		return new BelongsTo($builder, $parent, 'foreign_key');
	}

}

class EloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value';

}

class AnotherEloquentBelongsToModelStub extends Illuminate\Database\Eloquent\Model {

	public $foreign_key = 'foreign.value.two';

}