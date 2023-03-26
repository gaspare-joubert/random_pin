<?php

namespace GaspareJoubert\RandomPin\Tests;

use GaspareJoubert\RandomPin\RandomPINFacade;
use Orchestra\Testbench\TestCase;

class RandomPINFacadeTest extends TestCase
{
    protected $loadEnvironmentVariables = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            'GaspareJoubert\RandomPin\RandomPINServiceProvider',
        ];
    }

    public function testFailureGetPIN()
    {
        $pIN = RandomPINFacade::getPIN();
        $this->assertIsArray($pIN);
    }
}
