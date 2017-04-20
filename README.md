# RayGun.io integration for SilverStripe

This is a simple module that binds RayGun.io to the error & exception handler of SilverStripe.

## Installation

```
composer require silverstripe/raygun
```

## Configuration

Add the following to your `.env` file:

```
SS_RAYGUN_APP_KEY="dwhouq3845y98uiof=="
```

#### Set Log Level

You can define the error reporting level in your YAML config:

```yml
Graze\Monolog\Handler\RaygunHandler:
  constructor:
    level: 'error'
```

## Filtering

Some error data will be too sensitive to transmit to an external service, such as credit card details or passwords. Since this data is very application specific, Raygun doesn't filter out anything by default. You can configure to either replace or otherwise transform specific values based on their keys. These transformations apply to form data (`$_POST`), custom user data, HTTP headers, and environment data (`$_SERVER`). It does not filter the URL or its `$_GET` parameters, or custom message strings. Since Raygun doesn't log method arguments in stack traces, those don't need filtering. All key comparisons are case insensitive.

Example implementation in mysite/_config.php:

```php
<?php

$client = Injector::inst()->get(Raygun4php\RaygunClient::class);
$client->setFilterParams([
    'php_auth_pw' => true,
    '/password/i' => true,
	'Email' => function($key, $val) {
        return substr($val, 0, 5) . '...';
    }
]);
```

More information about accepted filtering formats is available
in the [Raygun4php](https://github.com/MindscapeHQ/raygun4php) documentation.
