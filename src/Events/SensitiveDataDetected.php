<?php

namespace YorCreative\Scrubber\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SensitiveDataDetected
{
    use Dispatchable;

    public function __construct(
        public readonly string $patternName,
        public readonly int $hitCount,
        public readonly string $context,
    ) {}
}
