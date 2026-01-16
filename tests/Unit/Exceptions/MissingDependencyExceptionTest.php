<?php

namespace YorCreative\Scrubber\Tests\Unit\Exceptions;

use PHPUnit\Framework\Attributes\Group;
use YorCreative\Scrubber\Exceptions\MissingDependencyException;
use YorCreative\Scrubber\Tests\TestCase;

#[Group('Exceptions')]
#[Group('Unit')]
class MissingDependencyExceptionTest extends TestCase
{
    public function test_it_can_be_instantiated_with_message()
    {
        $exception = new MissingDependencyException('Test message');

        $this->assertInstanceOf(MissingDependencyException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_it_can_be_instantiated_with_code()
    {
        $exception = new MissingDependencyException('Test message', 500);

        $this->assertEquals(500, $exception->getCode());
    }

    public function test_it_can_be_instantiated_with_previous_exception()
    {
        $previous = new \Exception('Previous exception');
        $exception = new MissingDependencyException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_for_package_creates_exception_with_formatted_message()
    {
        $exception = MissingDependencyException::forPackage('aws/aws-sdk-php', 'AWS Secrets Manager');

        $this->assertInstanceOf(MissingDependencyException::class, $exception);
        $this->assertEquals(
            'AWS Secrets Manager provider requires aws/aws-sdk-php. Install via: composer require aws/aws-sdk-php',
            $exception->getMessage()
        );
    }

    public function test_for_package_with_different_provider()
    {
        $exception = MissingDependencyException::forPackage('some/package', 'Custom Provider');

        $this->assertEquals(
            'Custom Provider provider requires some/package. Install via: composer require some/package',
            $exception->getMessage()
        );
    }
}
