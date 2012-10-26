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
		$query = m::mock('Illuminate\Database\Query\Builder');
		$query->shouldReceive('where')->once()->with('foo', '=', 'bar');
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('first'), array($query));
 		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getKeyName')->once()->andReturn('foo');
		$model->shouldReceive('getTable')->once()->andReturn('table');
		$query->shouldReceive('from')->once()->with('table');
		$builder->setModel($model);
		$builder->expects($this->once())->method('first')->with($this->equalTo(array('column')))->will($this->returnValue('baz'));
		$result = $builder->find('bar', array('column'));
		$this->assertEquals('baz', $result);
	}


	public function testFirstMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), $this->getMocks());
		$collection = m::mock('stdClass');
		$collection->shouldReceive('first')->once()->andReturn('bar');
		$builder->expects($this->once())->method('get')->with($this->equalTo(array('*')))->will($this->returnValue($collection));

		$result = $builder->first();
		$this->assertEquals('bar', $result);
	}


	public function testGetMethodLoadsModelsAndHydratesEagerRelations()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getModels', 'eagerLoadRelations'), $this->getMocks());
		$builder->expects($this->once())->method('getModels')->with($this->equalTo(array('foo')))->will($this->returnValue(array('bar')));
		$builder->expects($this->once())->method('eagerLoadRelations')->with($this->equalTo(array('bar')))->will($this->returnValue(array('bar', 'baz')));
		$results = $builder->get(array('foo'));

		$this->assertEquals(array('bar', 'baz'), $results->all());
	}


	public function testGetMethodDoesntHydrateEagerRelationsWhenNoResultsAreReturned()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getModels', 'eagerLoadRelations'), $this->getMocks());
		$builder->expects($this->once())->method('getModels')->with($this->equalTo(array('foo')))->will($this->returnValue(array()));
		$builder->expects($this->never())->method('eagerLoadRelations');
		$results = $builder->get(array('foo'));

		$this->assertEquals(array(), $results->all());
	}


	public function testPaginateMethod()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), $this->getMocks());
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getPerPage')->once()->andReturn(15);
		$model->shouldReceive('getTable')->once()->andReturn('foo_table');
		$query = $builder->getQuery();
		$query->shouldReceive('from')->once()->with('foo_table');
		$builder->setModel($model);
		$query->shouldReceive('getPaginationCount')->once()->andReturn(10);
		$conn = m::mock('stdClass');
		$paginator = m::mock('stdClass');
		$paginator->shouldReceive('getCurrentPage')->once()->andReturn(1);
		$conn->shouldReceive('getPaginator')->once()->andReturn($paginator);
		$query->shouldReceive('getConnection')->once()->andReturn($conn);
		$query->shouldReceive('forPage')->once()->with(1, 15);
		$collection = m::mock('stdClass');
		$collection->shouldReceive('all')->once()->andReturn(array('results'));
		$builder->expects($this->once())->method('get')->with($this->equalTo(array('*')))->will($this->returnValue($collection));
		$paginator->shouldReceive('make')->once()->with(array('results'), 10, 15)->andReturn(array('results'));

		$this->assertEquals(array('results'), $builder->paginate());
	}


	public function testGetModelsProperlyHydratesModels()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('get'), $this->getMocks());
		$records[] = array('id' => 1, 'name' => 'taylor', 'age' => 26);
		$records[] = array('id' => 23, 'name' => 'dayle', 'age' => 28);
		$builder->getQuery()->shouldReceive('get')->once()->with(array('foo'))->andReturn($records);
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getTable')->once()->andReturn('foobars');
		$builder->getQuery()->shouldReceive('from')->once()->with('foobars');
		$builder->setModel($model);
		$model->shouldReceive('getConnectionName')->once()->andReturn('foo_connection');
		$model->shouldReceive('newExisting')->twice()->andReturn(new EloquentBuilderTestModelStub, new EloquentBuilderTestModelStub);
		$models = $builder->getModels(array('foo'));

		$this->assertEquals('taylor', $models[1]->name);
		$this->assertEquals('dayle', $models[23]->name);
		$this->assertEquals('foo_connection', $models[1]->getConnectionName());
		$this->assertEquals('foo_connection', $models[23]->getConnectionName());
	}


	public function testEagerLoadRelationsLoadTopLevelRelationships()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('loadRelation'), $this->getMocks());
		$builder->setEagerLoads(array('foo' => function() {}, 'foo.bar' => function() {}));
		$builder->expects($this->once())->method('loadRelation')->with($this->equalTo(array('models')), $this->equalTo('foo'), $this->equalTo(function() {}))->will($this->returnValue(array('foo')));
		$results = $builder->eagerLoadRelations(array('models'));

		$this->assertEquals(array('foo'), $results);
	}


	public function testRelationshipEagerLoadProcess()
	{
		$builder = $this->getMock('Illuminate\Database\Eloquent\Builder', array('getRelation'), $this->getMocks());
		$builder->setEagerLoads(array('orders' => function($query) { $_SERVER['__eloquent.constrain'] = $query; }));
		$relation = m::mock('stdClass');
		$relation->shouldReceive('getAndResetWheres')->once()->andReturn(array(array('wheres'), array('bindings')));
		$relation->shouldReceive('addEagerConstraints')->once()->with(array('models'));
		$relation->shouldReceive('mergeWheres')->once()->with(array('wheres'), array('bindings'));
		$relation->shouldReceive('initRelation')->once()->with(array('models'), 'orders')->andReturn(array('models'));
		$relation->shouldReceive('get')->once()->andReturn(array('results'));
		$relation->shouldReceive('match')->once()->with(array('models'), array('results'), 'orders')->andReturn(array('models.matched'));
		$builder->expects($this->once())->method('getRelation')->with($this->equalTo('orders'))->will($this->returnValue($relation));
		$results = $builder->eagerLoadRelations(array('models'));

		$this->assertEquals(array('models.matched'), $results);
		$this->assertEquals($relation, $_SERVER['__eloquent.constrain']);
		unset($_SERVER['__eloquent.constrain']);
	}


	public function testGetRelationProperlySetsNestedRelationships()
	{
		$builder = $this->getBuilder();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$builder->getQuery()->shouldReceive('from')->once()->with('foo');
		$model->shouldReceive('getTable')->once()->andReturn('foo');
		$builder->setModel($model);
		$model->shouldReceive('orders')->once()->andReturn($relation = m::mock('stdClass'));
		$relationQuery = m::mock('stdClass');
		$relation->shouldReceive('getQuery')->andReturn($relationQuery);
		$relationQuery->shouldReceive('with')->once()->with(array('lines' => null, 'lines.details' => null));
		$builder->setEagerLoads(array('orders' => null, 'orders.lines' => null, 'orders.lines.details' => null));

		$relation = $builder->getRelation('orders');
	}


	public function testEagerLoadParsingSetsProperRelationships()
	{
		$builder = $this->getBuilder();
		$builder->with(array('orders', 'orders.lines'));
		$eagers = $builder->getEagerLoads();

		$this->assertEquals(array('orders', 'orders.lines'), array_keys($eagers));
		$this->assertInstanceOf('Closure', $eagers['orders']);
		$this->assertInstanceOf('Closure', $eagers['orders.lines']);

		$builder = $this->getBuilder();
		$builder->with(array('orders.lines'));
		$eagers = $builder->getEagerLoads();

		$this->assertEquals(array('orders', 'orders.lines'), array_keys($eagers));
		$this->assertInstanceOf('Closure', $eagers['orders']);
		$this->assertInstanceOf('Closure', $eagers['orders.lines']);

		$builder = $this->getBuilder();
		$builder->with(array('orders' => function() { return 'foo'; }));
		$eagers = $builder->getEagerLoads();

		$this->assertEquals('foo', $eagers['orders']());

		$builder = $this->getBuilder();
		$builder->with(array('orders.lines' => function() { return 'foo'; }));
		$eagers = $builder->getEagerLoads();

		$this->assertInstanceOf('Closure', $eagers['orders']);
		$this->assertNull($eagers['orders']());
		$this->assertEquals('foo', $eagers['orders.lines']());
	}


	public function testQueryPassThru()
	{
		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('foobar')->once()->andReturn('foo');

		$this->assertInstanceOf('Illuminate\Database\Eloquent\Builder', $builder->foobar());

		$builder = $this->getBuilder();
		$builder->getQuery()->shouldReceive('insert')->once()->with(array('bar'))->andReturn('foo');

		$this->assertEquals('foo', $builder->insert(array('bar')));
	}


	protected function getBuilder()
	{
		return new Builder(m::mock('Illuminate\Database\Query\Builder'));
	}


	protected function getMocks()
	{
		return array(m::mock('Illuminate\Database\Query\Builder'));
	}

}

class EloquentBuilderTestModelStub extends Illuminate\Database\Eloquent\Model {}