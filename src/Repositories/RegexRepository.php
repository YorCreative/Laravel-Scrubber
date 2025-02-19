<?php

namespace YorCreative\Scrubber\Repositories;

use Illuminate\Support\Collection;

class RegexRepository
{
    public function __construct(
        protected Collection $regexCollection
    ) {}

    public static function checkAndSanitize(string $regex, string $replace, string $content, int &$hits = 0): string
    {
        return preg_replace("~$regex~Si", $replace, $content, -1, $hits);
    }

    public static function check(string $regex, string $content): int
    {
        return preg_match_all("~$regex~Si", $content);
    }

    public function getRegexCollection(): Collection
    {
        return $this->regexCollection;
    }
}
