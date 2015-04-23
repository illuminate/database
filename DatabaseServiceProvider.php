<?php namespace Illuminate\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\QueueEntityResolver;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		Model::setConnectionResolver($this->app['db']);

		Model::setEventDispatcher($this->app['events']);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerQueueableEntityResolver();

		// The connection factory is used to create the actual connection instances on
		// the database. We will inject the factory into the manager so that it may
		// make the connections while they are actually needed and not of before.
		$this->app->singleton('db.factory', function($app)
		{
			return new ConnectionFactory($app);
		});

		// The database manager is used to resolve various connections, since multiple
		// connections might be managed. It also implements the connection resolver
		// interface which may be used by other components requiring connections.
		$this->app->singleton('db', function($app)
		{
			return new DatabaseManager($app, $app['db.factory']);
		});
	}

	/**
	 * Register the queueable entity resolver implementation.
	 *
	 * @return void
	 */
	protected function registerQueueableEntityResolver()
	{
		$this->app->singleton('Illuminate\Contracts\Queue\EntityResolver', function()
		{
			return new QueueEntityResolver;
		});
	}

}
