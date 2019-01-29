# RayGun.io integration for SilverStripe

This is a simple module that binds RayGun.io to the error & exception handler of SilverStripe.

## Installation

```
composer require silverstripe/raygun
```

## Configuration

Add the following to your `.env` file:

```ini
SS_RAYGUN_APP_KEY="dwhouq3845y98uiof=="
```

#### Set Log Level

You can define the error reporting level in your YAML config:

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Raygun\RaygunHandler:
    constructor:
      client: '%$Raygun4php\RaygunClient'
      level: 'error'
```

## Filtering

Raygun will send the following data:

- $_POST
- $_SERVER
- $_GET (included in URL also)

By default we filter out some sensitive SilverStripe details which appear in the $_SERVER variable. These include:

- SS_DATABASE_USERNAME
- SS_DATABASE_PASSWORD
- SS_DEFAULT_ADMIN_USERNAME
- SS_DEFAULT_ADMIN_PASSWORD
- SS_RAYGUN_APP_KEY

You will likely want to filter out other sensitive data such as credit cards, passwords etc. You can do this in your `mysite/_config.php` file. These rules are applied to $_SERVER, $_POST and $_GET data. All key comparisons are case insensitive.

Example implementation in mysite/_config.php:

```php
<?php

$client = Injector::inst()->get(Raygun4php\RaygunClient::class);
$client->setFilterParams(array_merge($client->getFilterParams(), [
    'php_auth_pw' => true,
    '/password/i' => true,
	'Email' => function($key, $val) {
        return substr($val, 0, 5) . '...';
    }
]));
```

More information about accepted filtering formats is available
in the [Raygun4php](https://github.com/MindscapeHQ/raygun4php) documentation.
