<?php namespace Illuminate\Database\Console\Migrations;

class RollbackCommand extends BasicMigrationCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:rollback';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Rollback the last database migration';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected function fire()
	{
		$package = $this->input->getArgument('package');

		$path = $this->getPackageMigrationPath($package);

		$this->migrator->rollbackMigrations($this->output, $package, $path, $pretend);
	}

}