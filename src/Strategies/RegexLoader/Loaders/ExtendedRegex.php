<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

class ExtendedRegex implements LoaderInterface
{
    /**
     * @var string
     */
    private string $path;

    public function __construct()
    {
        $this->path = base_path('App/Scrubber/RegexCollection');
    }

    /**
     * @return bool
     */
    public function canLoad(): bool
    {
        return File::exists($this->path);
    }

    /**
     * @param  Collection  $regexCollection
     */
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
