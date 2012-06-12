<?php namespace Illuminate\Database;

class MySqlConnection extends Connection {

	/**
	 * Get the default query grammar instance.
	 *
	 * @return Illuminate\Database\Query\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return new Query\Grammars\MySqlGrammar;
	}

	/**
	 * Get the default post processor instance.
	 *
	 * @return Illuminate\Database\Processors\Processor
	 */
	protected function getDefaultPostProcessor()
	{
		return new Processors\MySqlProcessor;
	}

}