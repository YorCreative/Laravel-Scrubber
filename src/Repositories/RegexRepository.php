<?php

namespace YorCreative\Scrubber\Repositories;

use Illuminate\Support\Collection;
use YorCreative\Scrubber\Strategies\RegexLoader\RegexLoaderStrategy;

class RegexRepository
{
    public Collection $regexCollection;

    public function __construct()
    {
        $this->regexCollection = new Collection();
        $this->loadRegexClasses();
    }

    protected function loadRegexClasses(): void
    {
        app(RegexLoaderStrategy::class)->load($this->regexCollection);
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

    /**
     * @return Collection
     */
    public function getRegexCollection(): Collection
    {
        return $this->regexCollection;
    }
}
