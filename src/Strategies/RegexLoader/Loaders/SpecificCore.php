<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

class SpecificCore implements LoaderInterface
{
    public function canLoad(): bool
    {
        return ! in_array('*', Config::get('scrubber.regex_loader'));
    }

    public function load(Collection &$regexCollection): void
    {
        foreach (Config::get('scrubber.regex_loader') as $regexClass) {
            if (class_exists(('YorCreative\Scrubber\RegexCollection\\'.$regexClass))) {
                $regex = (new ('YorCreative\Scrubber\RegexCollection\\'.$regexClass)());

                $regexCollection = $regexCollection->merge([
                    Str::snake($regexClass) => $regex,
                ]);
            }
        }
    }
}
