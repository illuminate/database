<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Migrations\MigrationCreator;

class MakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'db:migrate:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new migration file';

	/**
	 * The migration creaotor instance.
	 *
	 * @var Illuminate\Database\Migrations\MigrationCreator
	 */
	protected $creator;

	/**
	 * The paths to the migration files.
	 *
	 * @var array
	 */
	protected $paths;

	/**
	 * The path to the packages directory (vendor).
	 *
	 * @var string
	 */
	protected $packagePath;

	/**
	 * Create a new migration install command instance.
	 *
	 * @param  Illuminate\Database\Migrations\MigrationCreator  $creator
	 * @param  array  $paths
	 * @return void
	 */
	public function __construct(MigrationCreator $creator, array $paths, $packagePath)
	{
		parent::__construct();

		$this->paths = $paths;
		$this->creator = $creator;
		$this->packagePath = $packagePath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		// It's possible for the developer to specify the tables to modify in this
		// schema operation. The developer may also specify if this table needs
		// to be freshly created so we can create the appropriate migrations.
		$name = $this->input->getArgument('name');

		$table = $this->input->getOption('table');

		$create = $this->input->getOption('create');

		// Now we're ready to get the path where these migrations should be placed
		// on disk. This may be specified via the package option on the command
		// and we will verify that option to determine the appropriate paths.
		$path = $this->getPath();

		$this->creator->create($name, $path, $table, $create);

		$this->info('Migration created successfully!');
	}

	/**
	 * Get the path to the migration directory.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$package = $this->input->getOption('package');

		// If the package is in the list of migration paths we received we will put
		// the migrations in that path. Otherwise, we will assume the package is
		// is in the package directories and will place them in that location.
		if (isset($this->paths[$package]))
		{
			return $this->paths[$package];
		}

		return $this->packagePath.'/'.$package.'/src/migrations';
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
			array('package', null, InputOption::VALUE_OPTIONAL, 'The package the migration belongs to', 'application'),

			array('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate'),

			array('create', null, InputOption::VALUE_NONE, 'The table needs to be created'),
		);
	}

}