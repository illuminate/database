<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;

class EloquentCollectionTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testAddingItemsToCollection()
	{
		$c = new Collection(array('foo'));
		$c->add('bar')->add('baz');
		$this->assertEquals(array('foo', 'bar', 'baz'), $c->all());
	}


	public function testFirstReturnsFirstItemInCollection()
	{
		$c = new Collection(array('foo', 'bar'));
		$this->assertEquals('foo', $c->first());
	}


	public function testToArrayCallsToArrayOnEachItemInCollection()
	{
		$item1 = m::mock('stdClass');
		$item1->shouldReceive('toArray')->once()->andReturn('foo.array');
		$item2 = m::mock('stdClass');
		$item2->shouldReceive('toArray')->once()->andReturn('bar.array');
		$c = new Collection(array($item1, $item2));
		$results = $c->toArray();

		$this->assertEquals(array('foo.array', 'bar.array'), $results);
	}


	public function testToJsonEncodesTheToArrayResult()
	{
		$c = $this->getMock('Illuminate\Database\Eloquent\Collection', array('toArray'));
		$c->expects($this->once())->method('toArray')->will($this->returnValue('foo'));
		$results = $c->toJson();

		$this->assertEquals(json_encode('foo'), $results);
	}


	public function testCastingToStringJsonEncodesTheToArrayResult()
	{
		$c = $this->getMock('Illuminate\Database\Eloquent\Collection', array('toArray'));
		$c->expects($this->once())->method('toArray')->will($this->returnValue('foo'));

		$this->assertEquals(json_encode('foo'), (string) $c);
	}


	public function testOffsetAccess()
	{
		$c = new Collection(array('name' => 'taylor'));
		$this->assertEquals('taylor', $c['name']);
		$c['name'] = 'dayle';
		$this->assertEquals('dayle', $c['name']);
		$this->assertTrue(isset($c['name']));
		unset($c['name']);
		$this->assertFalse(isset($c['name']));
	}


	public function testCountable()
	{
		$c = new Collection(array('foo', 'bar'));
		$this->assertEquals(2, count($c));
	}


	public function testIterable()
	{
		$c = new Collection(array('foo'));
		$this->assertInstanceOf('ArrayIterator', $c->getIterator());
		$this->assertEquals(array('foo'), $c->getIterator()->getArrayCopy());
	}


	public function testLoadMethodEagerLoadsGivenRelationships()
	{
		$c = $this->getMock('Illuminate\Database\Eloquent\Collection', array('first'), array(array('foo')));
		$mockItem = m::mock('StdClass');
		$c->expects($this->once())->method('first')->will($this->returnValue($mockItem));
		$mockItem->shouldReceive('newQuery')->once()->andReturn($mockItem);
		$mockItem->shouldReceive('with')->with(array('bar', 'baz'))->andReturn($mockItem);
		$mockItem->shouldReceive('eagerLoadRelations')->once()->with(array('foo'))->andReturn(array('results'));
		$c->load('bar', 'baz');

		$this->assertEquals(array('results'), $c->all());
	}

}