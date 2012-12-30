<?php namespace Illuminate\Database\Query\Grammars;

class MySqlGrammar extends Grammar {

	/**
	 * The keyword identifier wrapper format.
	 *
	 * @var string
	 */
	protected $wrapper = '`%s`';

	/**
	 * Compile the query to determine if a table exists.
	 *
	 * @return string
	 */
	public function compileTableExists()
	{
		return 'show tables like ?';
	}

}