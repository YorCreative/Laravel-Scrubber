<?php

namespace YorCreative\Scrubber\RegexCollection;

use YorCreative\Scrubber\RegexCollectionInterface;

class FacebookAccessToken implements RegexCollectionInterface
{
    public function getPattern(): string
    {
        return 'EAACEdEose0cBA[0-9A-Za-z]+';
    }

    public function getTestableString(): string
    {
        return 'EAACEdEose0cBAdSAIzs2npcdqDa3vThXNiQx5JVD15rGwW';
    }
}
