<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

abstract class BasicMigrationCommand extends Command {

	/**
	 * The migrator instance.
	 *
	 * @var Illuminate\Database\Console\Migrations\Migrator
	 */
	protected $migrator;

	/**
	 * The default path to the migration files.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The path to the packages directory (vendor).
	 */
	protected $packagePath;

	/**
	 * Create a new migration rollback command instance.
	 *
	 * @param  Illuminate\Database\Console\Migrations\Migrator  $migrator
	 * @param  string  $path
	 * @param  string  $packagePath
	 * @return void
	 */
	public function __construct(Migrator $migrator, $path, $packagePath)
	{
		parent::__construct();

		$this->path = $path;
		$this->migrator = $migrator;
		$this->packagePath = $packagePath;
	}

	/**
	 * Get the path to a package's migrations.
	 *
	 * @param  string  $package
	 * @return string
	 */
	protected function getPackageMigrationPath($package)
	{
		if ($package == 'application') return $this->path;

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
			array('pretend', null, InputArgument::VALUE_NONE, 'Dump the SQL queries that would be run'),
		);
	}

}