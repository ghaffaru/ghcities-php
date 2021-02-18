# ghcities-php

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ghaffaru/ghcities-php.svg?style=flat-square)](https://packagist.org/packages/ghaffaru/ghcities-php)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ghaffaru/ghcities-php/run-tests?label=tests)](https://github.com/ghaffaru/ghcities-php/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/ghaffaru/ghcities-php.svg?style=flat-square)](https://packagist.org/packages/ghaffaru/ghcities-php)

A php package for working with cities in ghana !

## Installation

You can install the package via composer:

```bash
composer require ghaffaru/ghcities-php
```

## Usage

Get all Regions in ghana
``` php
$regions = \Ghaffaru\GhCities\Region::all();
```

Get all cities in Ghana
``` php
$cities = \Ghaffaru\GhCities\City::all();
```
## Testing

``` bash
composer test
```

## Contributing

Please see [CONTRIBUTING](./CONTRIBUTING.md) for details.

## Credits

- [Oliver](https://github.com/codingoliver/ghana-cities) for compiling the cities.json file

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
