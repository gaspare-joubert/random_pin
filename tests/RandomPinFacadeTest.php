<?php

namespace GaspareJoubert\RandomPin\Tests;

use GaspareJoubert\RandomPin\RandomPinFacade;
use Orchestra\Testbench\TestCase;

class RandomPinFacadeTest extends TestCase
{
    protected $loadEnvironmentVariables = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            'GaspareJoubert\RandomPin\RandomPinServiceProvider',
        ];
    }

    public function testFailureGetPIN()
    {
        $pin = RandomPinFacade::getPIN();
        $this->assertIsArray($pin);
    }
}
