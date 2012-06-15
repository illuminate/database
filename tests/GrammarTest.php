<?php

use Mockery as m;
use Illuminate\Database\Query\Builder;

class GrammarTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicSelect()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users');
		$this->assertEquals('select * from "users"', $builder->toSql());
	}


	public function testBasicSelectDistinct()
	{
		$builder = $this->getBuilder();
		$builder->distinct()->select('foo', 'bar')->from('users');
		$this->assertEquals('select distinct "foo", "bar" from "users"', $builder->toSql());
	}


	public function testBasicAlias()
	{
		$builder = $this->getBuilder();
		$builder->select('foo as bar')->from('users');
		$this->assertEquals('select "foo" as "bar" from "users"', $builder->toSql());
	}


	public function testBasicTableWrapping()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('public.users');
		$this->assertEquals('select * from "public"."users"', $builder->toSql());
	}


	public function testBasicWheres()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('id', '=', 1);
		$this->assertEquals('select * from "users" where "id" = ?', $builder->toSql());
		$this->assertEquals(array(0 => 1), $builder->getBindings());
	}


	public function testBasicOrWheres()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('id', '=', 1)->orWhere('email', '=', 'foo');
		$this->assertEquals('select * from "users" where "id" = ? or "email" = ?', $builder->toSql());
		$this->assertEquals(array(0 => 1, 1 => 'foo'), $builder->getBindings());
	}


	public function testBasicWhereIns()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->whereIn('id', array(1, 2, 3));
		$this->assertEquals('select * from "users" where "id" in (?, ?, ?)', $builder->toSql());
		$this->assertEquals(array(0 => 1, 1 => 2, 2 => 3), $builder->getBindings());

		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('id', '=', 1)->orWhereIn('id', array(1, 2, 3));
		$this->assertEquals('select * from "users" where "id" = ? or "id" in (?, ?, ?)', $builder->toSql());
		$this->assertEquals(array(0 => 1, 1 => 1, 2 => 2, 3 => 3), $builder->getBindings());
	}


	public function testLimitsAndOffsets()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->skip(5)->take(10);
		$this->assertEquals('select * from "users" limit 10 offset 5', $builder->toSql());
	}


	protected function getBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammar;

		$processor = m::mock('Illuminate\Database\Query\Processor');

		return new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
	}

}