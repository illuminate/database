<?php namespace Illuminate\Database;

class PostgresConnection extends Connection {

	/**
	 * Get the default query grammar instance.
	 *
	 * @return Illuminate\Database\Query\Grammars\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return new Query\Grammars\PostgresGrammar;
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return Illuminate\Database\Schema\Grammars\Grammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return new Schema\Grammars\PostgresGrammar;
	}

	/**
	 * Get the default post processor instance.
	 *
	 * @return Illuminate\Database\Query\Processors\Processor
	 */
	protected function getDefaultPostProcessor()
	{
		return new Query\Processors\PostgresProcessor;
	}

}