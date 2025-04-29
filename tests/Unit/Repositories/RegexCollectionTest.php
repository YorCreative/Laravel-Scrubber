<?php

namespace YorCreative\Scrubber\Tests\Unit\Repositories;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use YorCreative\Scrubber\Repositories\RegexCollection;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('RegexRepository')]
#[Group('Unit')]
class RegexCollectionTest extends TestCase
{
    public function test_it_can_verify_that_every_regex_class_available_is_a_static_property_on_regex_collection()
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
