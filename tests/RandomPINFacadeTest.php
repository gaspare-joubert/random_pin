<?php

namespace GaspareJoubert\RandomPin\Tests;

use GaspareJoubert\RandomPin\RandomPINFacade;
use GaspareJoubert\RandomPin\SetupPIN;
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
        $pin = RandomPINFacade::getPIN();
        $this->assertIsArray($pin);
    }

    public function testFailureIsPINRepeating()
    {
        $pin = '4567';
        $setup = new SetupPIN();
        $this->assertTrue($setup->isRepeating($pin), "{$pin} is not repeating numbers.");
    }
}
