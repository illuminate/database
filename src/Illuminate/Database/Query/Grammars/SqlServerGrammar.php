<?php namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;

class SqlServerGrammar extends Grammar {

	/**
	 * Compile a select query into SQL.
	 *
	 * @param  Illuminate\Database\Query\Builder
	 * @return string
	 */
	public function compileSelect(Builder $query)
	{
		$components = $this->compileComponents($query);

		// If an offset is present on the query, we will need to wrap the query in
		// a big ANSI offset syntax block. This is very nasty compared to the
		// other database systems, but is necessary for implementing this.
		if ($query->offset > 0)
		{
			return $this->compileAnsiOffset($query, $components);
		}

		return $this->concatenate($components);
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
		if ( ! is_null($query->aggregate)) return;

		$select = $query->distinct ? 'select distinct ' : 'select ';

		// If there is a limit on the query, but not an offset, we will add the
		// top clause to the query, which serves as a "limit" type clause in
		// SQL Server, since it does not have a separate keyword for this.
		if ($query->limit > 0 and $query->offset <= 0)
		{
			$select .= 'top '.$this->limit.' ';
		}

		return $select.$this->columnize($columns);
	}

	/**
	 * Create a full ANSI offset clause for the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $components
	 * @return string
	 */
	protected function compileAnsiOffset(Builder $query, $components)
	{
		// An ORDER BY clause is required to make this offset query work, so if
		// one doesn't exist, we'll just create a dummy clause to trick the
		// database and pacify it so it doesn't complain about the query.
		if ( ! isset($components['orders']))
		{
			$components['orders'] = 'order by (select 0)';
		}

		// We need to add the row number to the query so we can compare it to
		// the offset and limit values given for the statement. So we will
		// add an expression to the select that will give back the rows.
		$orders = $components['orders'];

		$components['selects'] .= ", row_number() over ({$orders}) as row_num";

		unset($components['orders']);

		$start = $query->offset + 1;

		// Next we need to calculate the constraint that should be placed on the
		// row number to get the correct offset and limit from our query, but
		// if there is not a limit specified we'll just handle the offset.
		if ($query->limit > 0)
		{
			$finish = $query->offset + $query->limit;

			$constraint = "between {$start} and {$finish}";
		}
		else
		{
			$constraint = ">= {$start}";
		}

		// We're finally ready to build the final SQL query so we'll create a
		// common table expression from the query and get the records with
		// row numbers being between the given limit and offset values.
		$sql = $this->concatenate($components);

		return "select * from ($sql) as temp_table where row_num {$constraint}";
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
		return '';
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
		return '';
	}

}