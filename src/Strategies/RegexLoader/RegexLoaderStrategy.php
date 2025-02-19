<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader;

use Illuminate\Support\Collection;

class RegexLoaderStrategy
{
    public Collection $availableLoaders;

    public function __construct()
    {
        $this->availableLoaders = new Collection;
    }

    public function setLoader(LoaderInterface $loader): void
    {
        $this->availableLoaders->push($loader);
    }

    public function load(): Collection
    {
        $regexCollection = new Collection;

        $this->availableLoaders->each(function ($loader) use (&$regexCollection) {
            if ($loader->canLoad()) {
                $loader->load($regexCollection);
            }
        });

        return $regexCollection;
    }
}
