<?php

use Mockery as m;
use Illuminate\Database\Query\Builder;

class BuilderTest extends PHPUnit_Framework_TestCase {

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


	public function testBasicWhereNotIns()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->whereNotIn('id', array(1, 2, 3));
		$this->assertEquals('select * from "users" where "id" not in (?, ?, ?)', $builder->toSql());
		$this->assertEquals(array(0 => 1, 1 => 2, 2 => 3), $builder->getBindings());

		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('id', '=', 1)->orWhereNotIn('id', array(1, 2, 3));
		$this->assertEquals('select * from "users" where "id" = ? or "id" not in (?, ?, ?)', $builder->toSql());
		$this->assertEquals(array(0 => 1, 1 => 1, 2 => 2, 3 => 3), $builder->getBindings());
	}


	public function testBasicWhereNulls()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->whereNull('id');
		$this->assertEquals('select * from "users" where "id" is null', $builder->toSql());
		$this->assertEquals(array(), $builder->getBindings());

		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('id', '=', 1)->orWhereNull('id');
		$this->assertEquals('select * from "users" where "id" = ? or "id" is null', $builder->toSql());
		$this->assertEquals(array(0 => 1), $builder->getBindings());
	}


	public function testBasicWhereNotNulls()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->whereNotNull('id');
		$this->assertEquals('select * from "users" where "id" is not null', $builder->toSql());
		$this->assertEquals(array(), $builder->getBindings());

		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('id', '>', 1)->orWhereNotNull('id');
		$this->assertEquals('select * from "users" where "id" > ? or "id" is not null', $builder->toSql());
		$this->assertEquals(array(0 => 1), $builder->getBindings());
	}


	public function testGroupBys()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->groupBy('id', 'email');
		$this->assertEquals('select * from "users" group by "id", "email"', $builder->toSql());
	}


	public function testOrderBys()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->orderBy('email')->orderBy('age', 'desc');
		$this->assertEquals('select * from "users" order by "email" asc, "age" desc', $builder->toSql());
	}


	public function testLimitsAndOffsets()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->skip(5)->take(10);
		$this->assertEquals('select * from "users" limit 10 offset 5', $builder->toSql());
	}


	public function testWhereShortcut()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('id', 1)->orWhere('name', 'foo');
		$this->assertEquals('select * from "users" where "id" = ? or "name" = ?', $builder->toSql());
		$this->assertEquals(array(0 => 1, 1 => 'foo'), $builder->getBindings());
	}


	public function testNestedWheres()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('email', '=', 'foo')->orWhere(function($q)
		{
			$q->where('name', '=', 'bar')->where('age', '=', 25);
		});
		$this->assertEquals('select * from "users" where "email" = ? or ("name" = ? and "age" = ?)', $builder->toSql());
		$this->assertEquals(array(0 => 'foo', 1 => 'bar', 2 => 25), $builder->getBindings());
	}


	public function testFullSubSelects()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->where('email', '=', 'foo')->orWhere('id', '=', function($q)
		{
			$q->select('raw|max(id)')->from('users')->where('email', '=', 'bar');
		});
		$this->assertEquals('select * from "users" where "email" = ? or "id" = (select max(id) from "users" where "email" = ?)', $builder->toSql());
		$this->assertEquals(array(0 => 'foo', 1 => 'bar'), $builder->getBindings());
	}


	public function testWhereExists()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('orders')->whereExists(function($q)
		{
			$q->select('*')->from('products')->where('products.id', '=', 'raw|"orders"."id"');
		});
		$this->assertEquals('select * from "orders" where exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

		$builder = $this->getBuilder();
		$builder->select('*')->from('orders')->whereNotExists(function($q)
		{
			$q->select('*')->from('products')->where('products.id', '=', 'raw|"orders"."id"');
		});
		$this->assertEquals('select * from "orders" where not exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

		$builder = $this->getBuilder();
		$builder->select('*')->from('orders')->where('id', '=', 1)->orWhereExists(function($q)
		{
			$q->select('*')->from('products')->where('products.id', '=', 'raw|"orders"."id"');
		});
		$this->assertEquals('select * from "orders" where "id" = ? or exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());

		$builder = $this->getBuilder();
		$builder->select('*')->from('orders')->where('id', '=', 1)->orWhereNotExists(function($q)
		{
			$q->select('*')->from('products')->where('products.id', '=', 'raw|"orders"."id"');
		});
		$this->assertEquals('select * from "orders" where "id" = ? or not exists (select * from "products" where "products"."id" = "orders"."id")', $builder->toSql());
	}


	public function testBasicJoins()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->join('contacts', 'users.id', '=', 'contacts.id')->leftJoin('photos', 'users.id', '=', 'photos.id');
		$this->assertEquals('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" left join "photos" on "users"."id" = "photos"."id"', $builder->toSql());
	}


	public function testComplexJoin()
	{
		$builder = $this->getBuilder();
		$builder->select('*')->from('users')->join('contacts', function($j)
		{
			$j->on('users.id', '=', 'contacts.id')->orOn('users.name', '=', 'contacts.name');
		});
		$this->assertEquals('select * from "users" inner join "contacts" on "users"."id" = "contacts"."id" or "users"."name" = "contacts"."name"', $builder->toSql());
	}


	public function testRawExpressionsInSelect()
	{
		$builder = $this->getBuilder();
		$builder->select('raw|substr(foo, 6)')->from('users');
		$this->assertEquals('select substr(foo, 6) from "users"', $builder->toSql());
	}


	public function testFindReturnsFirstResultByID()
	{
		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('select')->once()->with('select * from "users" where "id" = ? limit 1', array(1))->andReturn(array(array('foo' => 'bar')));
		$builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, array(array('foo' => 'bar')));
		$results = $builder->from('users')->find(1);
		$this->assertEquals(array('foo' => 'bar'), $results);
	}


	public function testFirstMethodReturnsFirstResult()
	{
		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('select')->once()->with('select * from "users" where "id" = ? limit 1', array(1))->andReturn(array(array('foo' => 'bar')));
		$builder->getProcessor()->shouldReceive('processSelect')->once()->with($builder, array(array('foo' => 'bar')));
		$results = $builder->from('users')->where('id', '=', 1)->first();
		$this->assertEquals(array('foo' => 'bar'), $results);	
	}


	public function testAggregateFunctions()
	{
		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('select')->once()->with('select count(*) as aggregate from "users"', array())->andReturn(array(array('aggregate' => 1)));
		$builder->getProcessor()->shouldReceive('processSelect')->once();
		$results = $builder->from('users')->count();
		$this->assertEquals(1, $results);

		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('select')->once()->with('select max("id") as aggregate from "users"', array())->andReturn(array(array('aggregate' => 1)));
		$builder->getProcessor()->shouldReceive('processSelect')->once();
		$results = $builder->from('users')->max('id');
		$this->assertEquals(1, $results);

		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('select')->once()->with('select min("id") as aggregate from "users"', array())->andReturn(array(array('aggregate' => 1)));
		$builder->getProcessor()->shouldReceive('processSelect')->once();
		$results = $builder->from('users')->min('id');
		$this->assertEquals(1, $results);

		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('select')->once()->with('select sum("id") as aggregate from "users"', array())->andReturn(array(array('aggregate' => 1)));
		$builder->getProcessor()->shouldReceive('processSelect')->once();
		$results = $builder->from('users')->sum('id');
		$this->assertEquals(1, $results);
	}


	public function testInsertMethod()
	{
		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('insert')->once()->with('insert into "users" ("email") values (?)', array('foo'))->andReturn(true);
		$result = $builder->from('users')->insert(array('email' => 'foo'));
		$this->assertTrue($result);
	}


	public function testInsertGetIdMethod()
	{
		$builder = $this->getBuilder();
		$builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" ("email") values (?)', array('foo'), 'id')->andReturn(1);
		$result = $builder->from('users')->insertGetId(array('email' => 'foo'), 'id');
		$this->assertEquals(1, $result);
	}


	public function testUpdateMethod()
	{
		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('update')->once()->with('update "users" set "email" = ?, "name" = ? where "id" = ?', array('foo', 'bar', 1))->andReturn(1);
		$result = $builder->from('users')->where('id', '=', 1)->update(array('email' => 'foo', 'name' => 'bar'));
		$this->assertEquals(1, $result);
	}


	public function testDeleteMethod()
	{
		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "email" = ?', array('foo'))->andReturn(1);
		$result = $builder->from('users')->where('email', '=', 'foo')->delete();
		$this->assertEquals(1, $result);

		$builder = $this->getBuilder();
		$builder->getConnection()->shouldReceive('delete')->once()->with('delete from "users" where "id" = ?', array(1))->andReturn(1);
		$result = $builder->from('users')->delete(1);
		$this->assertEquals(1, $result);
	}


	public function testPostgresInsertGetId()
	{
		$builder = $this->getPostgresBuilder();
		$builder->getProcessor()->shouldReceive('processInsertGetId')->once()->with($builder, 'insert into "users" ("email") values (?) returning "id"', array('foo'), 'id')->andReturn(1);
		$result = $builder->from('users')->insertGetId(array('email' => 'foo'), 'id');
		$this->assertEquals(1, $result);
	}


	public function testMySqlWrapping()
	{
		$builder = $this->getMySqlBuilder();
		$builder->select('*')->from('users');
		$this->assertEquals('select * from `users`', $builder->toSql());
	}


	public function testSQLiteOrderBy()
	{
		$builder = $this->getSQLiteBuilder();
		$builder->select('*')->from('users')->orderBy('email', 'desc');
		$this->assertEquals('select * from "users" order by "email" collate nocase desc', $builder->toSql());
	}


	public function testSqlServerLimitsAndOffsets()
	{
		$builder = $this->getSqlServerBuilder();
		$builder->select('*')->from('users')->take(10);
		$this->assertEquals('select top 10 * from "users"', $builder->toSql());

		$builder = $this->getSqlServerBuilder();
		$builder->select('*')->from('users')->skip(10);
		$this->assertEquals('select * from (select *, row_number() over (order by (select 0)) as row_num from "users") as temp_table where row_num >= 11', $builder->toSql());

		$builder = $this->getSqlServerBuilder();
		$builder->select('*')->from('users')->skip(10)->take(10);
		$this->assertEquals('select * from (select *, row_number() over (order by (select 0)) as row_num from "users") as temp_table where row_num between 11 and 20', $builder->toSql());

		$builder = $this->getSqlServerBuilder();
		$builder->select('*')->from('users')->skip(10)->take(10)->orderBy('email', 'desc');
		$this->assertEquals('select * from (select *, row_number() over (order by "email" desc) as row_num from "users") as temp_table where row_num between 11 and 20', $builder->toSql());
	}


	protected function getBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammars\Grammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
	}


	protected function getPostgresBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammars\PostgresGrammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
	}


	protected function getMySqlBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammars\MySqlGrammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
	}


	protected function getSQLiteBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammars\SQLiteGrammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
	}


	protected function getSqlServerBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammars\SqlServerGrammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
	}

}