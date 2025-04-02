<?php

namespace YorCreative\Scrubber\Tests\Unit\Repositories;

use Illuminate\Support\Collection;
use ReflectionClass;
use YorCreative\Scrubber\Repositories\RegexCollection;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Tests\TestCase;

class RegexCollectionTest extends TestCase
{
    /**
     * @test
     *
     * @group RegexRepository
     * @group Unit
     */
    public function it_can_verify_that_every_regex_class_available_is_a_static_property_on_regex_collection()
    {
        $class = new ReflectionClass(RegexCollection::class);
        $staticProperties = $class->getStaticProperties();

        $regexCollection = new Collection;
        app(RegexRepository::class)->getRegexCollection()->each(function ($regexClass) use ($regexCollection) {
            $regexCollection->push(class_basename($regexClass));
        });

        $this->assertEmpty(array_diff(array_values($staticProperties), $regexCollection->toArray()));
    }
}
