# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gaspare-joubert/random_pin.svg?style=flat-square)](https://packagist.org/packages/gaspare-joubert/random_pin)
[![Total Downloads](https://img.shields.io/packagist/dt/gaspare-joubert/random_pin.svg?style=flat-square)](https://packagist.org/packages/gaspare-joubert/random_pin)
![GitHub Actions](https://github.com/gaspare-joubert/random_pin/actions/workflows/main.yml/badge.svg)

A laravel package to generate random numerical PINs.

## Installation

You can install the package via composer:

```bash
composer require gaspare-joubert/random_pin
```
Next step is to publish the configuration for the package. This can be done using the command below
```bash
php artisan vendor:publish --provider="GaspareJoubert\RandomPin\RandomPinServiceProvider" 
```
This copies the package configuration into `config/random_pin.php`

Then you run migration to create the Random Pins tables

```bash
php artisan migrate
```

## Usage

```php
This package is compatible with Laravel 8+
You can provide an example of the PIN to generate
Either in the package config or by using an .env file
This example must be numerical with a maximum length of 8 characters
You can provide the number of PINs to get
Either in the package config or by using an .env file
```
Models which intend to generate PINs should extend the `GaspareJoubert\RandomPin\RandomPINFacade` facade

```php
// ...
use GaspareJoubert\RandomPin\RandomPINFacade;
// ...

class ExampleModel
{
    
}
```
### Generating PIN
```php 
$pin = RandomPINFacade::getPIN();
```
If pins are generated as expected a populated array would be returned, else an empty array would be returned

### Testing

```bash
$ phpunit RandomPINFacadeTest.php
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email gasparejoubert@gascosolutions.com instead of using the issue tracker.

## Credits

-   [Gaspare Joubert](https://github.com/gaspare-joubert)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
