<?php namespace Illuminate\Database;

class MySqlConnection extends Connection {

	/**
	 * Determine if the given table exists.
	 *
	 * @param  string  $table
	 * @return bool
	 */
	public function hasTable($table)
	{
		$sql = $this->queryGrammar->compileTableExists();

		return count($this->select($sql, array($this->database, $table))) > 0;
	}

	/**
	 * Get the default query grammar instance.
	 *
	 * @return Illuminate\Database\Query\Grammars\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\MySqlGrammar);
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return Illuminate\Database\Schema\Grammars\Grammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return $this->withTablePrefix(new Schema\Grammars\MySqlGrammar);
	}

}