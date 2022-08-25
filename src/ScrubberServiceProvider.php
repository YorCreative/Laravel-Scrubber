<?php

namespace YorCreative\Scrubber;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use YorCreative\Scrubber\Clients\GitLabClient;
use YorCreative\Scrubber\Handlers\ScrubberTap;
use YorCreative\Scrubber\Repositories\RegexRepository;

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

        if (Config::get('scrubber.secret_manager.enabled')
            && Config::get('scrubber.secret_manager.providers.gitlab.enabled')
        ) {
            $this->app->singleton(GitlabClient::class, function () {
                return new GitLabClient(new Client([
                    'base_uri' => Config::get('scrubber.secret_manager.providers.gitlab.host'),
                    'headers' => [
                        'accept' => 'application/json',
                        'content_type' => 'application/json',
                        'authorization' => 'bearer '.Config::get('scrubber.secret_manager.providers.gitlab.token'),
                    ],
                ]));
            });
        }

        $this->app->singleton(RegexRepository::class, function () {
            $regexRepository = new RegexRepository();

            return $regexRepository;
        });
    }
}
