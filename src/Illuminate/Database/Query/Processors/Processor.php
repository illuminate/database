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

}