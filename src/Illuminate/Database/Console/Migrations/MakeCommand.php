<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new migration file';

	/**
	 * The migration creaotor instance.
	 *
	 * @var Illuminate\Database\Console\Migrations\MigrationCreator
	 */
	protected $creator;

	/**
	 * Create a new migration install command instance.
	 *
	 * @param  Illuminate\Database\Console\Migrations\MigrationCreator  $creator
	 * @return void
	 */
	public function __construct(MigrationCreator $creator)
	{
		parent::__construct();

		$this->creator = $creator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected function fire()
	{
		$name = $this->input->getArgument('name');

		$this->creator->createMigration($name, $this->input->getOption('path'));
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the migration', null),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('path', 'p', InputOption::VALUE_OPTIONAL, 'Where to put the migration file', null),
		);
	}

}