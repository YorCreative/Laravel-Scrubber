<?php

namespace YorCreative\Scrubber\Strategies\RegexLoader;

use Illuminate\Support\Collection;

class RegexLoaderStrategy
{
    /**
     * @var Collection
     */
    public Collection $availableLoaders;

    public function __construct()
    {
        $this->availableLoaders = new Collection();
    }

    /**
     * @param  LoaderInterface  $loader
     */
    public function setLoader(LoaderInterface $loader): void
    {
        $this->availableLoaders->push($loader);
    }

    /**
     * @param  Collection  $regexCollection
     */
    public function load(Collection &$regexCollection): void
    {
        $this->availableLoaders->each(function ($loader) use (&$regexCollection) {
            if ($loader->canLoad()) {
                $loader->load($regexCollection);
            }
        });
    }
}
