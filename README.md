# Yammer API Wrapper For Laravel

[![Build Status](https://travis-ci.org/bilalbaraz/laravel-yammer-client.svg?branch=master)](https://travis-ci.org/bilalbaraz/laravel-yammer-client)

## Installation

Run in console below command to download package to your project:

```bash
composer require bilalbaraz/laravel-yammer-client
```

## Requirements

```bash
guzzlehttp/guzzle
```

## Configuration

Publish config settings:

```bash
php artisan vendor:publish --provider="Yammer\YammerServiceProvider"
```

or copy ```/vendor/bilalbaraz/laravel-yammer-client/config/yammer.php``` into ```/config``` directory.

and add below global variables to your .env file:

```
YAMMER_CLIENT_ID=[YAMMER-CLIENT-ID WILL BE HERE]
YAMMER_CLIENT_SECRET=[YAMMER-CLIENT-SECRET WILL BE HERE]
YAMMER_TOKEN=[YAMMER-ACCESS-TOKEN WILL BE HERE]
```

## Usage

Where you want to use this package, must be added below namespace:
```
use Yammer\YammerClient;
```
