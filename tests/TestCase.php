<?php

namespace YorCreative\Scrubber\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Monolog\LogRecord;
use YorCreative\Scrubber\Clients\GitLabClient;
use YorCreative\Scrubber\ScrubberServiceProvider;
use YorCreative\Scrubber\Support\LogRecordFactory;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public array $record;

    public string $mockSecretsResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->record = [
            'message' => 'hello, world.',
            'context' => [],
            'level' => 200,
            'level_name' => 'INFO',
            'datetime' => '2022-08-15T22:12:32.502986+00:00',
            'extra' => [],
        ];

        $this->mockSecretsResponse = $this->getMockFor('get_gitlab_variables');

        $this->createGitlabClientMock([
            new Response(200, [], $this->mockSecretsResponse),
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            ScrubberServiceProvider::class,
        ];
    }

    public function createGitlabClientMock(array $responses)
    {
        $this->instance(
            GitLabClient::class,
            new GitLabClient(new Client(['handler' => new MockHandler($responses)]))
        );
    }

    protected function getMockFor($filename)
    {
        if (! Str::endsWith($filename, '.json')) {
            $filename .= '.json';
        }

        return file_get_contents(__DIR__.'/Mocks/'.$filename);
    }

    protected function getTestLogRecord(\DateTimeImmutable $datetime, string $message, array $context): LogRecord
    {
        $channel = 'test_channel';
        $level = 200;
        $extra = [];

        return LogRecordFactory::buildRecord($datetime, $channel, $level, $message, $context, $extra);
    }
}
