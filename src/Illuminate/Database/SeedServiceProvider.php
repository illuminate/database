<?php namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Console\SeedCommand;

class SeedServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$this->registerSeedCommand($app);

		$app['seeder'] = $app->share(function($app)
		{
			return new Seeder($app['files'], $app['events']);
		});
	}

	/**
	 * Register the seed console command.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerSeedCommand($app)
	{
		$app['command.seed'] = $app->share(function($app)
		{
			$path = $app['path'].'/database/seeds';

			return new SeedCommand($app['db'], $app['seeder'], $app['events'], $path);
		});
	}

}