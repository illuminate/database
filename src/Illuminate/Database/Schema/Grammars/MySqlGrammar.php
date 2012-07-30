<?php namespace Illuminate\Database\Schema\Grammars;

use Illuminate\Support\Fluent;
use Illuminate\Database\Schema\Blueprint;

class MySqlGrammar extends Grammar {

	/**
	 * The keyword identifier wrapper format.
	 *
	 * @var string
	 */
	protected $wrapper = '`%s`';

	/**
	 * Compile a create table command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return ?
	 */
	public function compileCreate(Blueprint $blueprint, Fluent $command)
	{
		//
	}

	/**
	 * Compile a create table command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return ?
	 */
	public function compileAdd(Blueprint $blueprint, Fluent $command)
	{
		//
	}

}