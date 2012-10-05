<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class ResetCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:reset';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Rollback all database migrations';

	/**
	 * The migrator instance.
	 *
	 * @var Illuminate\Database\Console\Migrations\Migrator
	 */
	protected $migrator;

	/**
	 * Create a new migration rollback command instance.
	 *
	 * @param  Illuminate\Database\Console\Migrations\Migrator  $migrator
	 * @return void
	 */
	public function __construct(Migrator $migrator)
	{
		parent::__construct();

		$this->migrator = $migrator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected function fire()
	{
		$pretend = $this->input->getOption('pretend');

		while ($this->migrator->rollbackMigrations($this->output, $pretend)) {}
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('pretend', null, InputArgument::VALUE_NONE, 'Dump the SQL queries that would be run'),
		);
	}

}