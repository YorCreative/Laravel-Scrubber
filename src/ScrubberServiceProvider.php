<?php

namespace YorCreative\Scrubber;

use Illuminate\Support\ServiceProvider;
use YorCreative\Scrubber\Handlers\ScrubberTap;

class ScrubberServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__, 1).'/config/scrubber.php', 'scrubber');
        $this->commands(
            'YorCreative\\Scrubber\\Commands\\MakeRegexClass'
        );

        $this->publishes([
            dirname(__DIR__, 1).'/config' => base_path('config'),
        ]);
    }

    public function boot()
    {
        $this->app->make('config')->set('logging.channels.single.tap', [
            ScrubberTap::class,
        ]);
    }
}
