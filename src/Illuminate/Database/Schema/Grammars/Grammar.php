<?php namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Grammar as BaseGrammar;

abstract class Grammar extends BaseGrammar {

	/**
	 * Get the SQL for the column data type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function getType(Fluent $column)
	{
		return $this->{"type".ucfirst($column->type)}($column);
	}

	/**
	 * Add a prefix to an array of values.
	 *
	 * @param  string  $prefix
	 * @param  array   $values
	 * @return array
	 */
	public function prefixArray($prefix, array $values)
	{
		return array_map(function($value) use ($prefix)
		{
			return $prefix.' '.$value;

		}, $values);
	}

	/**
	 * Wrap a table in keyword identifiers.
	 *
	 * @param  mixed   $table
	 * @return string
	 */
	public function wrapTable($table)
	{
		if ($table instanceof Blueprint) $table = $table->getTable();

		return parent::wrapTable($table);
	}

	/**
	 * Wrap a value in keyword identifiers.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		if ($value instanceof Fluent) $value = $value->name;

		return parent::wrap($value);
	}

}