<?php namespace Illuminate\Database;

abstract class Grammar {

	/**
	 * Convert an array of column names into a delimited string.
	 *
	 * @param  array   $columns
	 * @return string
	 */
	public function columnize(array $columns)
	{
		return implode(', ', $columns);
	}

}