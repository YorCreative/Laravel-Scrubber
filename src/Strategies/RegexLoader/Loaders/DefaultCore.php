<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

class DefaultCore implements LoaderInterface
{
    /**
     * @return bool
     */
    public function canLoad(): bool
    {
        return in_array('*', Config::get('scrubber.regex_loader'));
    }

    /**
     * @param  Collection  $collection
     */
    public function load(Collection &$regexCollection): void
    {
        foreach (File::files(dirname(__DIR__, 3).'/RegexCollection') as $regexClass) {
            $regex = (new ('YorCreative\Scrubber\RegexCollection\\'.$regexClass->getFilenameWithoutExtension())());

            $regexCollection = $regexCollection->merge([
                Str::snake($regexClass->getFilenameWithoutExtension()) => $regex,
            ]);
        }
    }
}
