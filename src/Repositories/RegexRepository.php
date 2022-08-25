<?php

namespace YorCreative\Scrubber\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RegexRepository
{
    /**
     * @return Collection
     */
    public static function getRegexCollection(): Collection
    {
        $regexCollection = new Collection();

        self::loadRegexClasses($regexCollection);

        return $regexCollection;
    }

    protected static function loadRegexClasses(Collection &$collection): void
    {
        /**
         * Default Regex Collection
         */
        foreach (File::files(dirname(__DIR__, 1).'/RegexCollection') as $regexClass) {
            $regex = (new ('YorCreative\Scrubber\RegexCollection\\'.$regexClass->getFilenameWithoutExtension())());

            $collection = $collection->merge([
                Str::snake($regexClass->getFilenameWithoutExtension()) => $regex,
            ]);
        }

        /**
         * Extended Regex Collection
         */
        $path = base_path('App/Scrubber/RegexCollection');

        if (File::exists($path)) {
            foreach (File::files($path) as $regexClass) {
                $regex = (new ('App\\Scrubber\\RegexCollection\\'.$regexClass->getFilenameWithoutExtension())());

                $collection = $collection->merge([
                    Str::snake($regexClass->getFilenameWithoutExtension()) => $regex,
                ]);
            }
        }
    }

    /**
     * @param  string  $regex
     * @param  string  $content
     * @param  int  $hits
     * @return string
     */
    public static function checkAndSanitize(string $regex, string $content, int &$hits = 0): string
    {
        return preg_replace("~$regex~i", config('scrubber.redaction'), $content, -1, $hits);
    }
}
