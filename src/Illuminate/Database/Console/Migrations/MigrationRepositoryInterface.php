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
	 * Get the last migration batch.
	 *
	 * @return array
	 */
	public function getLastMigrations();

	/**
	 * Log that a migration was run.
	 *
	 * @param  string  $package
	 * @param  string  $file
	 * @param  int     $batch
	 * @return void
	 */
	public function log($package, $file, $batch);

	/**
	 * Remove that a migration from the log.
	 *
	 * @param  StdClass  $migration
	 * @return void
	 */
	public function delete($migration);

	/**
	 * Get the next migration batch number.
	 *
	 * @return int
	 */
	public function getNextBatchNumber();

	/**
	 * Create the migration repository data store.
	 *
	 * @return void
	 */
	public function createRepository();

}