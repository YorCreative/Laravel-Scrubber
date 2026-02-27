<?php

namespace YorCreative\Scrubber\Commands;

use Illuminate\Console\Command;
use YorCreative\Scrubber\Repositories\RegexRepository;
use YorCreative\Scrubber\SecretManager\Secret;

class ValidateRegex extends Command
{
    protected $signature = 'scrubber:validate';

    protected $description = 'Validate all loaded regex patterns against their testable strings.';

    public function handle(): int
    {
        $regexRepository = app(RegexRepository::class);
        $collection = $regexRepository->getRegexCollection();

        if ($collection->isEmpty()) {
            $this->warn('No regex patterns loaded.');

            return self::SUCCESS;
        }

        $passed = 0;
        $failed = 0;
        $rows = [];

        foreach ($collection as $regexClass) {
            $pattern = $regexClass->isSecret()
                ? Secret::decrypt($regexClass->getPattern())
                : $regexClass->getPattern();

            $testable = $regexClass->getTestableString();
            $className = class_basename($regexClass);

            try {
                $result = preg_match("~{$pattern}~Si", $testable);
                if ($result === 1) {
                    $rows[] = [$className, 'PASS', ''];
                    $passed++;
                } elseif ($result === 0) {
                    $rows[] = [$className, 'FAIL', 'Pattern did not match testable string'];
                    $failed++;
                } else {
                    $rows[] = [$className, 'ERROR', 'preg_match returned false'];
                    $failed++;
                }
            } catch (\Exception $e) {
                $rows[] = [$className, 'ERROR', $e->getMessage()];
                $failed++;
            }
        }

        $this->table(['Pattern', 'Status', 'Message'], $rows);
        $this->info("Results: {$passed} passed, {$failed} failed, ".count($collection).' total.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
