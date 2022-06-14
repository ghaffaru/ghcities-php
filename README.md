# ghcities-php
[![Latest Stable Version](https://poser.pugx.org/ghaffaru/ghcities-php/v)](//packagist.org/packages/ghaffaru/ghcities-php)
[![Total Downloads](https://poser.pugx.org/ghaffaru/ghcities-php/downloads)](//packagist.org/packages/ghaffaru/ghcities-php)
[![License](https://poser.pugx.org/ghaffaru/ghcities-php/license)](//packagist.org/packages/ghaffaru/ghcities-php)

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

Get Cities in a Region
``` php
$cities = \Ghaffaru\GhCities\Region::getCities('Ahafo');
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
