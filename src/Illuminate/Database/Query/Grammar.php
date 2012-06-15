<?php namespace Illuminate\Database\Query;

use Illuminate\Database\Grammar as BaseGrammar;

class Grammar extends BaseGrammar {

	/**
	 * The components that make up a select clause.
	 *
	 * @var array
	 */
	protected $selectComponents = array(
		'aggregate',
		'columns',
		'from',
		'joins',
		'wheres',
		'groups',
		'havings',
		'orders',
		'limit',
		'offset',
	);

	/**
	 * Compile a select query into SQL.
	 *
	 * @param  Illuminate\Database\Query\Builder
	 * @return string
	 */
	public function compileSelect(Builder $query)
	{
		$sql = array();

		foreach ($this->selectComponents as $component)
		{
			// To compile the query, we'll spin through each component of the query and
			// see if that component exists. If it does, we'll just call the compiler
			// function for that component which is responsible for making the SQL.
			if ( ! is_null($query->$component))
			{
				$method = 'compile'.ucfirst($component);

				$sql[$component] = $this->$method($query, $query->$component);
			}
		}

		return trim($this->concatenate($sql));
	}

	/**
	 * Compile an aggregated select clause.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $aggregate
	 * @return string
	 */
	protected function compileAggregate(Builder $query, $aggregate)
	{
		$column = $this->columnize($aggregate['columns']);

		// If the query has a "distinct" constraint and we're not asking for all columns
		// we need to prepend "distinct" onto the column name so that the query takes
		// it into account when it performs the aggregating operations on the data.
		if ($query->distinct and $column !== '*')
		{
			$column = 'distinct '.$column;
		}

		return 'select '.$aggregate['function'].'('.$column.') as aggregate';
	}

	/**
	 * Compile the "select *" portion of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $columns
	 * @return string
	 */
	protected function compileColumns(Builder $query, $columns)
	{
		// If the query is actually performing an aggregating select, we'll let
		// that compiler handle the building of the select clause, as it will
		// need some special syntax that is best handleed by that function.
		if ( ! is_null($query->aggregate)) return;

		$select = $query->distinct ? 'select distinct ' : 'select ';

		return $select.$this->columnize($columns);
	}

	/**
	 * Compile the "from" portion of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  string  $table
	 * @return string
	 */
	protected function compileFrom(Builder $query, $table)
	{
		return "from $table";
	}

	/**
	 * Compile the "join" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $joins
	 * @return string
	 */
	protected function compileJoins(Builder $query, $joins)
	{
		$sql = array();

		foreach ($joins as $join)
		{
			$table = $join['table'];

			// First we need to build all of the "on" clauses for the join. There may
			// be many of these clauses, so we will need to spin through each one
			// and built it separately, then we will join them up at the end.
			$clauses = array();

			foreach ($join['conditions'] as $condition)
			{
				extract($condition);

				$clauses[] = "$boolean $first $operator $second";
			}

			// Once we have constructed the clauses, we'll need to take the boolean
			// connector off of the first clause since it obviously will not be
			// needed on that clause since it leads the rest of the clauses.
			$search = array('and ', 'or ');

			$clauses[0] = str_replace($search, '', $clauses[0]);

			$clauses = implode(' ', $clauses);

			$type = $join['type'];

			// Once we have everything ready to go, we'll just concatenate all the
			// parts to build the final "join" statement SQL for the query and
			// we can then return it back to the caller as a single string.
			$sql[] = "$type join $table on $clauses";
		}

		return implode(' ', $sql);
	}

	/**
	 * Compile the "where" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $wheres
	 * @return string
	 */
	protected function compileWheres(Builder $query, $wheres)
	{
		$sql = array();

		// Each type of where clause has its own compiler function whichi is responsible
		// for actually creating the where clause SQL. This helps keep the code nice
		// and maintainable since each clause has a very small function it uses.
		foreach ($wheres as $where)
		{
			$method = "where{$where['type']}";

			$sql[] = $where['boolean'].' '.$this->$method($query, $where);
		}

		// If we actually have some where clauses, we will strip off the first boolean
		// opeartor, which is added by the query builder for convenience so we can
		// avoid checking for the first clause in each of the compiler methods.
		if (count($sql) > 0)
		{
			$sql = implode(' ', $sql);

			return 'where '.preg_replace('/and |or /', '', $sql, 1);
		}

		return '';
	}

	/**
	 * Compile the "group by" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $groups
	 * @return string
	 */
	protected function compileGroups(Builder $query, $groups)
	{
		return 'group by '.$this->columnize($groups);
	}

	/**
	 * Compile the "having" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $havings
	 * @return string
	 */
	protected function compileHavings(Builder $query, $havings)
	{
		return '';
	}

	/**
	 * Compile the "order by" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $orders
	 * @return string
	 */
	protected function compileOrders(Builder $query, $orders)
	{
		return 'order by '.implode(', ', array_map(function($order)
		{
			return $order['column'].' '.$order['direction'];
		}
		, $orders));
	}

	/**
	 * Compile the "limit" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  int  $limit
	 * @return string
	 */
	protected function compileLimit(Builder $query, $limit)
	{
		return "limit $limit";
	}

	/**
	 * Compile the "offset" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  int  $offset
	 * @return string
	 */
	protected function compileOffset(Builder $query, $offset)
	{
		return "offset $offset";
	}

	/**
	 * Concatenate an array of segments, removing empties.
	 *
	 * @param  array   $segments
	 * @return string
	 */
	protected function concatenate($segments)
	{
		return implode(' ', array_filter($segments, function($value)
		{
			return (string) $value !== '';
		}));
	}

}