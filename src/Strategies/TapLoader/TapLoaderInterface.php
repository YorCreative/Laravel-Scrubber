<?php

namespace YorCreative\Scrubber\Strategies\TapLoader;

use Illuminate\Config\Repository;

interface TapLoaderInterface
{
    public function canLoad(): bool;

    public function load(Repository &$config): void;
}
