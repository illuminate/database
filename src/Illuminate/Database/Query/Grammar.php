<?php namespace Illuminate\Database\Query;

use Illuminate\Database\Grammar as BaseGrammar;

abstract class Grammar extends BaseGrammar {

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
		'having',
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

		return $this->concatenate($sql);
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
		// it into account when it performs the aggregating operation on the data.
		if ($query->distinct and $column != '*')
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