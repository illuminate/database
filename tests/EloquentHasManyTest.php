<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentHasManyTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCreateMethodProperlyCreatesNewModel()
	{
		$relation = $this->getRelation();
		$created = $this->getMock('Illuminate\Database\Eloquent\Model', array('save', 'getKey'));
		$created->expects($this->once())->method('save')->will($this->returnValue(true));
		$created->expects($this->once())->method('getKey')->will($this->returnValue(1));
		$relation->getRelated()->shouldReceive('newInstance')->once()->with(array('name' => 'taylor', 'foreign_key' => 1))->andReturn($created);

		$this->assertEquals(1, $relation->create(array('name' => 'taylor')));
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', m::type('Illuminate\Database\Eloquent\Collection'));
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('foreign_key', array(1, 2));
		$model1 = new EloquentHasManyModelStub;
		$model1->id = 1;
		$model2 = new EloquentHasManyModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$relation = $this->getRelation();

		$result1 = new EloquentHasManyModelStub;
		$result1->id = 1;
		$result1->foreign_key = 1;
		$result2 = new EloquentHasManyModelStub;
		$result2->id = 2;
		$result2->foreign_key = 2;
		$result3 = new EloquentHasManyModelStub;
		$result3->id = 3;
		$result3->foreign_key = 2;

		$model1 = new EloquentHasManyModelStub;
		$model1->id = 1;
		$model2 = new EloquentHasManyModelStub;
		$model2->id = 2;
		$model3 = new EloquentHasManyModelStub;
		$model3->id = 3;

		$models = $relation->match(array($model1, $model2, $model3), new Collection(array($result1, $result2, $result3)), 'foo');

		$this->assertEquals(1, $models[0]->foo[1]->foreign_key);
		$this->assertEquals(1, count($models[0]->foo));
		$this->assertEquals(2, $models[1]->foo[2]->foreign_key);
		$this->assertEquals(2, $models[1]->foo[3]->foreign_key);
		$this->assertEquals(2, count($models[1]->foo));
		$this->assertEquals(0, count($models[2]->foo));
	}


	protected function getRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('where')->with('foreign_key', '=', 1);
		$related = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		return new HasMany($builder, $parent, 'foreign_key');
	}

}

class EloquentHasManyModelStub extends Illuminate\Database\Eloquent\Model {
	public $foreign_key = 'foreign.value';
}