<?php

namespace YorCreative\Scrubber\Interfaces;

interface RegexCollectionInterface
{
    public function isSecret(): bool;

    public function getPattern(): string;

    public function getTestableString(): string;
}
