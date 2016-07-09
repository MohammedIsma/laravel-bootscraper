
# Laravel Bootscraper

[![Laravel](https://img.shields.io/badge/Laravel-~5.0-orange.svg?style=flat-square)](http://laravel.com)
[![Total Downloads](http://img.shields.io/packagist/dt/misma/laravel-bootscraper.svg?style=flat-square)](https://packagist.org/packages/misma/laravel-bootscraper)

Laravel Bootscraper generates clean laravel layouts based on bootstrap templates. This package will import any bootstrap template and generate the necessary view files to use the template in any Laravel project.

## Table of Contents
* [Requirements](#requirements)
* [Installation](#getting-started)
* [Documentation](#documentation)
* [Contribution Guidelines](#contribution-guidelines)


## <a name="requirements"></a>Requirements

* This package requires PHP 5.2+

## <a name="getting-started"></a>Installation

1. Require the package in your project's `composer.json` in one of the following ways
   - Command line: Run `composer require "misma/laravel-bootscraper:dev-master"` 
   OR-
   - Composer.json: Add 'misma/laravel-bootscraper": "dev-master",'

2. Update your project dependencies with `composer update`:


3. Add the package to the service providers array in `config/app.php`.
   > `Misma\Bootscraper\BootscrapeServiceProvider::class`


4. Publish the package configuration and related files
   `php artisan vendor:publish`


5. Modify `config/bootscraper.php` to fit project requirements

## <a name="documentation"></a>Documentation

Follow along the [Wiki](https://github.com/mohammedisma/laravel-bootscraper/wiki) to find out more.

## <a name="contribution-guidelines"></a>Contribution Guidelines

Support follows PSR-2 PHP coding standards, and semantic versioning.

Please report any issue you find in the issues page.
Pull requests are welcome.