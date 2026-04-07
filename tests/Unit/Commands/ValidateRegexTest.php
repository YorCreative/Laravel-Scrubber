<?php

namespace YorCreative\Scrubber\Tests\Unit\Commands;

use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Interfaces\RegexCollectionInterface;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('Commands')]
#[Group('Unit')]
class ValidateRegexTest extends TestCase
{
    public function test_validate_command_handles_pattern_containing_tilde()
    {
        $regexClass = new class implements RegexCollectionInterface
        {
            public function isSecret(): bool
            {
                return false;
            }

            public function getPattern(): string
            {
                return '~abc~';
            }

            public function getTestableString(): string
            {
                return 'token ~abc~ should match';
            }
        };

        $regexRepository = new RegexRepository(collect(['tilde_test' => $regexClass]));
        $this->app->instance(RegexRepository::class, $regexRepository);

        $this->artisan('scrubber:validate')
            ->expectsOutputToContain('PASS')
            ->assertExitCode(0);
    }

    public function test_validate_command_passes_with_normal_patterns()
    {
        $this->artisan('scrubber:validate')
            ->expectsOutputToContain('PASS')
            ->assertExitCode(0);
    }
}
