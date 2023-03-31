<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader\Loaders;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use YorCreative\Scrubber\Strategies\RegexLoader\LoaderInterface;

class SpecificExtendedRegex implements LoaderInterface
{
    private string $path;

    public function __construct()
    {
        $this->path = base_path('app/Scrubber/RegexCollection');
    }

    public function canLoad(): bool
    {
        return File::exists($this->path)
            && ! in_array('*', Config::get('scrubber.regex_loader'));
    }

    public function load(Collection &$regexCollection): void
    {
        foreach (File::files($this->path) as $regexClass) {
            $regexClassPath = 'App\\Scrubber\\RegexCollection\\'.$regexClass->getFilenameWithoutExtension();
            $regexClassName = $regexClass->getFilenameWithoutExtension();
            $configRegexLoader = Config::get('scrubber.regex_loader');

            if (in_array($regexClassPath, $configRegexLoader)
                || in_array($regexClassName, $configRegexLoader)) {
                $regex = (new ('App\\Scrubber\\RegexCollection\\'.$regexClass->getFilenameWithoutExtension())());

                $regexCollection = $regexCollection->merge([
                    Str::snake($regexClass->getFilenameWithoutExtension()) => $regex,
                ]);
            }
        }
    }
}
