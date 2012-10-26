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
		$c->add('bar');
		$this->assertEquals(array('foo', 'bar'), $c->all());
	}


	public function testFirstReturnsFirstItemInCollection()
	{
		$c = new Collection(array('foo', 'bar'));
		$this->assertEquals('foo', $c->first());
	}


	public function testContainsMethod()
	{
		$c = new Collection(array(1 => new stdClass, 2 => null, 4 => false));
		$this->assertTrue($c->contains(1));
		$this->assertTrue($c->contains(2));
		$this->assertFalse($c->contains(3));
		$this->assertTrue($c->contains(4));
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

}