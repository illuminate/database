<?php namespace Illuminate\Database\Query\Processors;

use Illuminate\Database\Query\Builder;

abstract class Processor {

	/**
	 * Process the results of a "select" query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array  $results
	 * @return array
	 */
	public function processSelect(Builder $query, $results)
	{
		return $results;
	}

	/**
	 * Process the results of an "insert get ID" query.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @param  array   $results
	 * @param  string  $sequence
	 * @return array
	 */
	public function processInsertGetId(Builder $query, $result, $sequence = null)
	{
		return $query->getConnection()->getPdo()->lastInsertId($sequence);
	}

}