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
	 * The possible column modifiers.
	 *
	 * @var array
	 */
	protected $modifiers = array('Unsigned', 'Nullable', 'Default', 'Increment');

	/**
	 * Compile a create table command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileCreate(Blueprint $blueprint, Fluent $command)
	{
		$columns = implode(', ', $this->getColumns($blueprint));

		return 'create table '.$this->wrapTable($blueprint)." ($columns)";
	}

	/**
	 * Compile a create table command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileAdd(Blueprint $blueprint, Fluent $command)
	{
		$table = $this->wrapTable($blueprint);

		$columns = $this->prefixArray('add', $this->getColumns($blueprint));

		return 'alter table '.$table.' '.implode(', ', $columns);
	}

	/**
	 * Compile a primary key command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compilePrimary(Blueprint $blueprint, Fluent $command)
	{
		$command->name(null);

		return $this->compileKey($blueprint, $command, 'primary key');
	}

	/**
	 * Compile a unique key command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileUnique(Blueprint $blueprint, Fluent $command)
	{
		return $this->compileKey($blueprint, $command, 'unique');
	}

	/**
	 * Compile a plain index key command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileIndex(Blueprint $blueprint, Fluent $command)
	{
		return $this->compileKey($blueprint, $command, 'index');
	}

	/**
	 * Compile an index creation command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @param  string  $type
	 * @return string
	 */
	protected function compileKey(Blueprint $blueprint, Fluent $command, $type)
	{
		$columns = $this->columnize($command->columns);

		$table = $this->wrapTable($blueprint);

		return "alter table {$table} add {$type} {$command->name}($columns)";
	}

	/**
	 * Compile a drop table command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDrop(Blueprint $blueprint, Fluent $command)
	{
		return 'drop table '.$this->wrapTable($blueprint);
	}

	/**
	 * Compile a drop column command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileDropColumn(Blueprint $blueprint, Fluent $command)
	{
		$columns = $this->prefixArray('drop', $this->wrapArray($command->columns));

		$table = $this->wrapTable($blueprint);

		return 'alter table '.$table.' '.implode(', ', $columns);
	}

	/**
	 * Compile a rename table command.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $command
	 * @return string
	 */
	public function compileRename(Blueprint $blueprint, Fluent $command)
	{
		$from = $this->wrapTable($blueprint);

		return "rename table {$from} to ".$this->wrapTable($command->to);
	}

	/**
	 * Compile the blueprint's column definitions.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @return array
	 */
	protected function getColumns(Blueprint $blueprint)
	{
		$columns = array();

		foreach ($blueprint->getColumns() as $column)
		{
			// Each of the column types have their own compiler functions which are
			// responsible for turning the column definition into its SQL format
			// for the platform. Then column modifiers are compiled and added.
			$sql = $this->wrap($column).' '.$this->getType($column);

			$columns[] = $this->addModifiers($sql, $blueprint, $column);
		}

		return $columns;
	}

	/**
	 * Create the column definition for a string type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeString(Fluent $column)
	{
		return "varchar({$column->length})";
	}

	/**
	 * Create the column definition for a text type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeText(Fluent $column)
	{
		return 'text';
	}

	/**
	 * Create the column definition for a integer type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeInteger(Fluent $column)
	{
		return 'int';
	}

	/**
	 * Create the column definition for a float type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeFloat(Fluent $column)
	{
		return 'float({$column->places}, {$column->total})';
	}

	/**
	 * Create the column definition for a decimal type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeDecimal(Fluent $column)
	{
		return 'decimal({$column->places}, {$column->total})';
	}

	/**
	 * Create the column definition for a boolean type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeBoolean(Fluent $column)
	{
		return 'tinyint';
	}

	/**
	 * Create the column definition for a date type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeDate(Fluent $column)
	{
		return 'date';
	}

	/**
	 * Create the column definition for a date-time type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeDateTime(Fluent $column)
	{
		return 'datetime';
	}

	/**
	 * Create the column definition for a time type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeTime(Fluent $column)
	{
		return 'time';
	}

	/**
	 * Create the column definition for a timestamp type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeTime(Fluent $column)
	{
		return 'timestamp';
	}

	/**
	 * Create the column definition for a binary type.
	 *
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function typeBinary(Fluent $column)
	{
		return 'blob';
	}

	/**
	 * Add the column modifiers to the definition.
	 *
	 * @param  string  $sql
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function addModifiers($sql, Blueprint $blueprint, Fluent $column)
	{
		foreach ($this->modifiers as $modifier)
		{
			$sql .= $this->{"modify{$modifier}"}($blueprint, $column);
		}

		return $sql;
	}

	/**
	 * Get the SQL for an unsigned column modifier.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string|null
	 */
	protected function modifyUnsigned(Blueprint $blueprint, Fluent $column)
	{
		if ($column->type == 'integer' and $column->unsigned)
		{
			return ' unsigned';
		}
	}

	/**
	 * Get the SQL for a nullable column modifier.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string|null
	 */
	protected function modifyNullable(Blueprint $blueprint, Fluent $column)
	{
		return $column->nullable ? ' null' : ' not null';
	}

	/**
	 * Get the SQL for a default column modifier.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string|null
	 */
	protected function modifyDefault(Blueprint $blueprint, Fluent $column)
	{
		if ( ! is_null($column->default))
		{
			return " default '".$column->default."'";
		}
	}

	/**
	 * Get the SQL for an auto-increment column modifier.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  Illuminate\Support\Fluent  $column
	 * @return string|null
	 */
	protected function modifyIncrement(Blueprint $blueprint, Fluent $column)
	{
		if ($column->type == 'integer' and $column->autoIncrement)
		{
			return ' auto_increment primary key';
		}
	}

}