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

}