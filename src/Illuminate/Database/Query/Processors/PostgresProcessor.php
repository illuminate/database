<?php namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

class PostgresProcessor extends Processor {

	/**
	 * Process an "insert get ID" query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  string  $sql
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return int
	 */
	public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
	{
		$results = $query->getConnection()->select($sql, $values);

		if( ! isset($results[0]))
			throw new Exception("The ID could not be retrieved.");

		return $results[0]->$sequence;
	}

}
