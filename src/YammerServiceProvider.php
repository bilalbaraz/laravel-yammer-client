<?php

namespace Yammer;

use Illuminate\Support\ServiceProvider;

/**
 *
 */
class YammerServiceProvider extends ServiceProvider
{

    /**
  	 * Indicates if loading of the provider is deferred.
  	 *
  	 * @var bool
  	 */
  	protected $defer = false;

  	public function boot()
  	{
        $this->publishes([
            __DIR__.'/../config/yammer.php' => config_path('yammer.php'),
        ]);
    }


    public function register()
  	{
  		config([
  				'config/yammer.php',
  		]);
  	}
}
