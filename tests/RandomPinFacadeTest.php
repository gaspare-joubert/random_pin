<?php

namespace GaspareJoubert\RandomPin\Tests;

use GaspareJoubert\RandomPin\Models\RandomPin;
use GaspareJoubert\RandomPin\RandomPinFacade;
use GaspareJoubert\RandomPin\RandomPinServiceProvider;
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
            RandomPinServiceProvider::class,
        ];
    }

    /**
     * @test
     * @covers
     */
    public function testFailureGetPin()
    {
        $expectedCount = config('random_pin.required_application_parameters.number_of_pins_to_get') ?? false;

        if ($expectedCount > 0) {
            $pins = RandomPinFacade::getPin();
            $this->assertIsArray($pins,'An array of pins not returned.');
            $this->assertCount($expectedCount, $pins, "The expected number of pins, {$expectedCount}, was not returned.");

            foreach ($pins as $pin) {
                $this->assertIsString($pin, "The generated pin,'{$pin}', is not a string.");
            }
        } else {
            print_r('Unable to test GetPin. Could not determine the number of pins to be returned.');
        }
    }

    /**
     * @covers
     * @return void
     */
    public function testFailureGetNumericalPinRange()
    {
        $pinLength = config('random_pin.required_application_parameters.pin_length.length') ?? false;

        if ($pinLength) {
            $test = RandomPinFacade::getNumericalPinRange($pinLength);
            $this->assertIsArray($test, 'Not an array of a numerical pin range');
            $this->assertArrayHasKey('Min', $test, "Pin range does not contain 'Min' key.");
            $this->assertArrayHasKey('Max', $test, "Pin range does not contain 'Max' key.");
        } else {
            print_r('Unable to test GetNumericalPinRange. Could not determine the pin length to be used.');
        }
    }

    /**
     * @covers
     * @return void
     */
    public function testFailureGetApplicationParameters()
    {
        $expectedCount = config('random_pin.required_application_parameters') ? count(config('random_pin.required_application_parameters')) : false;
        if ($expectedCount > 0) {
            $applicationParameters = RandomPinFacade::getApplicationParameters();
            $this->assertIsArray($applicationParameters, 'An array of application parameters not returned.');
            $this->assertCount($expectedCount, $applicationParameters, "{$expectedCount} application parameters have not been returned");
        } else {
            print_r('Unable to test GetApplicationParameters. The number of application parameters could not be determined.');
        }
    }

    /**
     * @covers
     * @return void
     */
    public function testFailureIsApplicationParametersValid()
    {
        $this->assertTrue(RandomPinFacade::isApplicationParametersValid(), 'Not all parameter conditions were met.');
    }

    /**
     * @covers
     * @return void
     */
    public function testFailureGetPinTypeNumerical()
    {
        $permittedCharacters = config('random_pin.required_application_parameters.permitted_characters') ?? false;
        $numericalPinType = RandomPin::TYPE_NUMERICAL ?? false;
        if ($permittedCharacters && $numericalPinType) {
            $test = RandomPinFacade::getPinType($permittedCharacters);
            $this->assertIsInt($test, 'The pin type is not an integer.');
            $this->assertEquals(RandomPin::TYPE_NUMERICAL, $test, "The pin type does not match numerical: {$numericalPinType}");
        } else {
            print_r('Unable to test GetPinTypeNumerical. The pin type or permitted characters could not be determined.');
        }
    }

    /**
     * Test generating numerical pins only.
     * Returns false if generating pins failed.
     *
     * @covers
     * @return void
     */
    public function testFailureGeneratePinsNumerical()
    {
        $numericalPinType = RandomPin::TYPE_NUMERICAL ?? false;
        $pinLength = config('random_pin.required_application_parameters.pin_length.length') ?? false;
        if ($pinLength && $numericalPinType) {
            $this->assertTrue(RandomPinFacade::generatePins($numericalPinType, $pinLength), "Generating numerical pins failed.");
        } else {
            print_r('Unable to test GeneratePinsNumerical. The pin type or length could not be determined.');
        }
    }
}
