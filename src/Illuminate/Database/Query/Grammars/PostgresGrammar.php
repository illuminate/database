<?php namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;

class PostgresGrammar extends Grammar {

	/**
	 * Compile the query to determine if a table exists.
	 *
	 * @return string
	 */
	public function compileTableExists()
	{
		return 'select * from information_schema.tables where table_name = ?';
	}

	/**
	 * Compile an insert and get ID statement into SQL.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return string
	 */
	public function compileInsertGetId(Builder $query, $values, $sequence)
	{
		if (is_null($sequence)) $sequence = 'id';

		return $this->compileInsert($query, $values).' returning '.$this->wrap($sequence);
	}

}