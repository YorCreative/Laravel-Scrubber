<?php

namespace YorCreative\Scrubber\Repositories;

use Illuminate\Support\Collection;

class RegexRepository
{
    public function __construct(
        protected Collection $regexCollection
    ) {
    }

    public static function checkAndSanitize(string $regex, string $content, int &$hits = 0): string
    {
        if ($regex == '/password/') {
            if (strpos($content, "password") !== false) {
                $regex = '/"password":\s*"([^"]+)"/i';
                if (preg_match($regex, $content, $matches, PREG_OFFSET_CAPTURE, 0)) {
                    return str_replace($matches[1][0], config('scrubber.redaction'), $content);
                }
            }
        }

        return preg_replace($regex, config('scrubber.redaction'), $content, -1, $hits);
    }

    public static function check(string $regex, string $content): int
    {
        return preg_match_all($regex, $content);
    }

    public function getRegexCollection(): Collection
    {
        return $this->regexCollection;
    }
}
