<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

class WildcardExtendedRegex implements LoaderInterface
{
    private string $path;

    public function __construct()
    {
        $regexCollectionPath = config::get('scrubber.regex_collection_app_path', 'Scrubber/RegexCollection');
        $this->path = app_path($regexCollectionPath);
    }

    public function canLoad(): bool
    {
        return File::exists($this->path) && in_array('*', Config::get('scrubber.regex_loader'));
    }

    public function load(Collection &$regexCollection): void
    {
        foreach (File::files($this->path) as $regexClass) {
            $regex = (new ('App\\Scrubber\\RegexCollection\\'.$regexClass->getFilenameWithoutExtension())());

            $regexCollection = $regexCollection->merge([
                Str::snake($regexClass->getFilenameWithoutExtension()) => $regex,
            ]);
        }
    }
}
