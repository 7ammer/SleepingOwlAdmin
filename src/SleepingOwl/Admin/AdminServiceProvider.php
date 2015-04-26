<?php namespace SleepingOwl\Admin;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{

	protected $providers = [
		'SleepingOwl\AdminAuth\AdminAuthServiceProvider',
		'SleepingOwl\Admin\Providers\DisplayServiceProvider',
		'SleepingOwl\Admin\Providers\ColumnServiceProvider',
		'SleepingOwl\Admin\Providers\FormServiceProvider',
		'SleepingOwl\Admin\Providers\FormItemServiceProvider',
		'SleepingOwl\Admin\Providers\FilterServiceProvider',
		'SleepingOwl\Admin\Providers\BootstrapServiceProvider',
		'SleepingOwl\Admin\Providers\RouteServiceProvider',
	];

	protected $commads = [
		'AdministratorsCommand',
		'InstallCommand',
		'ModelCommand'
	];

	public function register()
	{
		$this->registerCommands();
	}

	public function boot()
	{
		$this->loadViewsFrom(__DIR__ . '/../../views', 'admin');
		$this->loadTranslationsFrom(__DIR__ . '/../../lang', 'admin');
		$this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'admin');

		$this->publishes([
			__DIR__ . '/../../config/config.php' => config_path('admin.php'),
		], 'config');

		$this->publishes([
			__DIR__ . '/../../migrations/' => base_path('/database/migrations'),
		], 'migrations');

		$this->publishes([
			__DIR__ . '/../../../public/' => public_path('packages/sleeping-owl/admin/'),
		], 'assets');

		Admin::instance();
		$this->registerTemplate();
		$this->registerProviders();
		$this->initializeTemplate();
	}

	/**
	 * @return array
	 */
	public function provides()
	{
		return ['admin'];
	}

	protected function registerTemplate()
	{
		app()->bind('adminTemplate', function ()
		{
			return Admin::instance()->template();
		});
	}

	protected function initializeTemplate()
	{
		app('adminTemplate');
	}

	protected function registerProviders()
	{
		foreach ($this->providers as $providerClass)
		{
			$provider = app($providerClass, [app()]);
			$provider->register();
		}
	}

	protected function registerCommands()
	{
		foreach ($this->commads as $command)
		{
			$this->commands('SleepingOwl\Admin\Commands\\' . $command);
		}
	}

}