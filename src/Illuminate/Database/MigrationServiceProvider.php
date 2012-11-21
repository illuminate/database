<?php namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\MakeCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class MigrationServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$this->registerRepository($app);

		$this->registerMigrator($app);

		// Once we have registered the migrator instance we will go ahead and register
		// all of the migration related commands that are used by the "Artisan" CLI
		// so that they may be easily accessed for registering with the consoles.
		$this->registerCommands($app);

		$this->registerPostCreationHook($app);
	}

	/**
	 * Register the migration repository service.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerRepository($app)
	{
		$app['migration.repository'] = $app->share(function($app)
		{
			$table = $app['config']['database.migration.table'];

			return new DatabaseMigrationRepository($app['db'], $table);
		});
	}

	/**
	 * Register the migrator service.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerMigrator($app)
	{
		// The migrator is responsible for actually running and rollback the migration
		// files in the application. We'll pass in our database connection resolver
		// so the migrator can resolve any of these connections when it needs to.
		$app['migrator'] = $app->share(function($app)
		{
			$repository = $app['migration.repository'];

			return new Migrator($repository, $app['db'], $app['files']);
		});
	}

	/**
	 * Register all of the migration commands.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerCommands($app)
	{
		$commands = array('Migrate', 'Rollback', 'Reset', 'Refresh', 'Install', 'Make');

		// We'll simply spin through the list of commands that are migration related
		// and register each one of them with an application container. They will
		// be resolved in the Artisan start file and registered on the console.
		foreach ($commands as $command)
		{
			$this->{'register'.$command.'Command'}($app);
		}
	}

	/**
	 * Register the "migrate" migration command.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerMigrateCommand($app)
	{
		$app['command.migrate'] = $app->share(function($app)
		{
			$paths = $app['config']['database.migration.paths'];

			$packagePath = $app['path.base'].'/vendor';

			return new MigrateCommand($app['migrator'], $paths, $packagePath);
		});
	}

	/**
	 * Register the "rollback" migration command.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerRollbackCommand($app)
	{
		$app['command.migrate.rollback'] = $app->share(function($app)
		{
			return new RollbackCommand($app['migrator']);
		});
	}

	/**
	 * Register the "reset" migration command.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerResetCommand($app)
	{
		$app['command.migrate.reset'] = $app->share(function($app)
		{
			return new ResetCommand($app['migrator']);
		});
	}

	/**
	 * Register the "refresh" migration command.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerRefreshCommand($app)
	{
		$app['command.migrate.refresh'] = $app->share(function($app)
		{
			return new RefreshCommand;
		});
	}

	/**
	 * Register the "install" migration command.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerInstallCommand($app)
	{
		$app['command.migrate.install'] = $app->share(function($app)
		{
			return new InstallCommand($app['migration.repository']);
		});
	}

	/**
	 * Register the "install" migration command.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerMakeCommand($app)
	{
		$app['migration.creator'] = $app->share(function($app)
		{
			return new MigrationCreator($app['files']);
		});

		$app['command.migrate.make'] = $app->share(function($app)
		{
			// Once we have the migration creator registered, we will create the command
			// and inject the creator. The creator is responsible for the actual file
			// creation of the migrations, and may be extended by these developers.
			$creator = $app['migration.creator'];

			$paths = $app['config']['database.migration.paths'];

			$packagePath = $app['path.base'].'/vendor';

			return new MakeCommand($creator, $paths, $packagePath);
		});
	}

	/**
	 * Register the migration post create hook.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerPostCreationHook($app)
	{
		$app->extend('migration.creator', function($creator, $app)
		{
			// After a new migration is created, we will tell the Composer manager to
			// regenerate the auto-load files for the framework. This simply makes
			// sure that a migration will get immediately available for loading.
			$creator->afterCreate(function() use ($app)
			{
				$app['composer']->dumpAutoloads();
			});

			return $creator;
		});
	}

}