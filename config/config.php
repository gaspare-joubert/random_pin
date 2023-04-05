<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'permitted_characters' => env('RANDOM_PIN_PERMITTED_CHARACTERS','1234'),
    'pin_length' => env('RANDOM_PIN_LENGTH','4'),
    'number_of_pins_to_get' => env('NUMBER_OF_PINS_TO_GET','1'),
];