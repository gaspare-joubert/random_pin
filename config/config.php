<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    // The parameters required in order for the application to continue operation.
    'required_application_parameters' => [
        'permitted_characters' => env('RANDOM_PIN_PERMITTED_CHARACTERS','0123456789'),
        'pin_length' => env('RANDOM_PIN_LENGTH','4'),
        'number_of_pins_to_get' => env('NUMBER_OF_PINS_TO_GET','1'),
    ],
    'application_parameter_conditions' => [
        'pin_length_min' => [
            'statement' => 'required_application_parameters.pin_length.length',
            'operator' => '>=',
            'argument' => 'required_application_parameters.pin_length.min_length',
        ],
        'pin_length_max' => [
            'statement' => 'required_application_parameters.pin_length.length',
            'operator' => '<=',
            'argument' => 'required_application_parameters.pin_length.max_length',
        ],
    ],
];