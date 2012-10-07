<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrateCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run the database migrations';

	/**
	 * The migrator instance.
	 *
	 * @var Illuminate\Database\Migrations\Migrator
	 */
	protected $migrator;

	/**
	 * The paths to the migrations.
	 *
	 * @var array
	 */
	protected $paths;

	/**
	 * The path to the packages directory (vendor).
	 */
	protected $packagePath;

	/**
	 * Create a new migration command instance.
	 *
	 * @param  Illuminate\Database\Migrations\Migrator  $migrator
	 * @param  array   $paths
	 * @param  string  $packagePath
	 * @return void
	 */
	public function __construct(Migrator $migrator, array $paths, $packagePath)
	{
		parent::__construct();

		$this->paths = $paths;
		$this->migrator = $migrator;
		$this->packagePath = $packagePath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$package = $this->input->getArgument('package');

		// The pretend option can be used for "simulating" the migration and grabbing
		// the SQL queries that would fire if the migration were to be run against
		// a database for real, which is helpful for double checking migrations.
		$pretend = $this->input->getOption('pretend');

		$path = $this->getPackageMigrationPath($package);

		$this->migrator->runMigrations($this->output, $package, $path, $pretend);
	}

	/**
	 * Get the path to a package's migrations.
	 *
	 * @param  string  $package
	 * @return string
	 */
	protected function getPackageMigrationPath($package)
	{
		if (isset($this->paths[$package])) return $this->paths[$package];

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
			array('package', InputArgument::OPTIONAL, 'The package to migrate', 'application'),
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
			array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run'),
		);
	}

}