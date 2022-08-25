<?php

namespace YorCreative\Scrubber;

interface RegexCollectionInterface
{
    public function getPattern(): string;

    public function getTestableString(): string;
}
