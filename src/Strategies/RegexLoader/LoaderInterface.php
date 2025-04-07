<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader;

use Illuminate\Support\Collection;

interface LoaderInterface
{
    public function canLoad(): bool;

    public function load(Collection &$regexCollection): void;
}
