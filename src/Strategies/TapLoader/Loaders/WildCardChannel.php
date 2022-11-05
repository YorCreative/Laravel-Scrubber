<?php

namespace YorCreative\Scrubber\Strategies\TapLoader\Loaders;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Config;
use YorCreative\Scrubber\Handlers\ScrubberTap;
use YorCreative\Scrubber\Strategies\TapLoader\TapLoaderInterface;

class WildCardChannel implements TapLoaderInterface
{
    /**
     * @return bool
     */
    public function canLoad(): bool
    {
        $channels = Config::get('scrubber.tap_channels');
        if (! $channels) {
            return false;
        }

        return in_array('*', Config::get('scrubber.tap_channels'));
    }

    /**
     * @param  Repository  $config
     */
    public function load(Repository &$config): void
    {
        $channels = $config->get('logging.channels');

        foreach ($channels as $key => $channel) {
            $config->set("logging.channels.$key.tap", [
                ScrubberTap::class,
            ]);
        }
    }
}
