<?php

namespace YorCreative\Scrubber\Strategies\TapLoader\Loaders;

use Illuminate\Config\Repository;
use YorCreative\Scrubber\Handlers\ScrubberTap;

class DefaultChannels extends WildCardChannel
{
    final public function canLoad(): bool
    {
        return true;
    }

    public function load(Repository $config): void
    {
        $channels = $config->get('logging.channels');

        foreach ($channels as $key => $channel) {
            $config->set("logging.channels.$key.tap", [
                ScrubberTap::class,
            ]);
        }
    }
}
