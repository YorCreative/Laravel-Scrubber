<?php

namespace YorCreative\Scrubber\Strategies\TapLoader;

use Illuminate\Config\Repository;
use Illuminate\Support\Collection;

class TapLoaderStrategy
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
     * @param  TapLoaderInterface  $loader
     */
    public function setLoader(TapLoaderInterface $loader): void
    {
        $this->availableLoaders->push($loader);
    }

    /**
     * @param  Repository  $config
     */
    public function load(Repository &$config): void
    {
        $this->availableLoaders->each(function ($loader) use (&$config) {
            if ($loader->canLoad()) {
                $loader->load($config);
            }
        });
    }
}
