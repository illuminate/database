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
	 * The default path to the migrations.
	 *
	 * @var string
	 */
	protected $path;

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
	public function __construct(MigrationCreator $creator, $path)
	{
		parent::__construct();

		$this->path = $path;
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

		// It's possible for the developer to specify the tables to modify in this
		// schema operation. The developer may also specify if this table needs
		// to be freshly created so we can create the appropriate migrations.
		$table = $this->input->getOption('table');

		$create = $this->input->getOption('create');

		$this->creator->create($name, $this->getPath(), $table, $create);

		$this->info('Enjoy your new migration!');
	}

	/**
	 * Get the path to the migration directory.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$path = $this->input->getOption('path');

		if ( ! is_null($path))
		{
			return str_replace('{app}', $this->laravel['path'], $path);
		}

		return $this->path;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the migration'),
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
			array('path', null, InputOption::VALUE_OPTIONAL, 'Where to put the migration file'),

			array('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate'),

			array('create', null, InputOption::VALUE_NONE, 'The table needs to be created'),
		);
	}

}