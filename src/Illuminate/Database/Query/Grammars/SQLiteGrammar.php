<?php namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;

class SQLiteGrammar extends Grammar {

	/**
	 * Compile the "order by" portions of the query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $orders
	 * @return string
	 */
	protected function compileOrders(Builder $query, $orders)
	{
		$me = $this;

		return 'order by '.implode(', ', array_map(function($order) use ($me)
		{
			return $me->wrap($order['column']).' collate nocase '.$order['direction'];
		}
		, $orders));
	}

	/**
	 * Compile an insert statement into SQL.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $values
	 * @return string
	 */
	public function compileInsert(Builder $query, array $values)
	{
		// Essentially we will force every insert to be treated as a batch insert which
		// simply makes creating the SQL easier for us since we can utilize the same
		// basic routine regardless of an amount of records given to us to insert.
		$table = $this->wrapTable($query->from);

		if ( ! is_array(reset($values)))
		{
			$values = array($values);
		}

		// If there is only one record being inserted, we will just use the usual query
		// grammar insert builder because no special syntax is needed for the single
		// row inserts in SQLite. However, if there are multiples, we'll continue.
		if (count($values) == 1)
		{
			return parent::compileInsert($query, $values[0]);
		}

		$columns = array();
		$columnsNames = array_keys($values[0]);

		// SQLite requires us to build the multi-row insert as a listing of select with
		// unions joining them together. So we'll build out this list of columns and
		// then join them all together with select unions to complete the queries.
		foreach ($columnsNames as $column)
		{
			$column = '? as '.$this->wrap($column);
			if (!empty($columns)) $column = ', ' .$column;

			$columns[] = $column;
		}

		$columns = array_fill(9, count($values), implode($columns));
		$columnsNames = implode(', ', $columnsNames);

		return "insert into $table($columnsNames) select ".implode(' union select ', $columns);
	}

}
