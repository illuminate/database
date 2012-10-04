<?php namespace Illuminate\Database\Console\Migrations;

interface MigrationRepositoryInterface {

	/**
	 * Get the ran migrations for a given package.
	 *
	 * @param  string  $package
	 * @return array
	 */
	public function getRanMigrations($package);

	/**
	 * Log that a migration was run.
	 *
	 * @param  string  $package
	 * @param  string  $file
	 * @param  int     $batch
	 * @return void
	 */
	public function logMigration($package, $file, $batch);

	/**
	 * Get the next migration batch number.
	 *
	 * @return int
	 */
	public function getNextBatchNumber();

}