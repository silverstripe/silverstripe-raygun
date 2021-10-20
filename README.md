# Raygun integration for SilverStripe

This is a simple module that binds Raygun to the error & exception handler of SilverStripe.

## Installation

```
composer require silverstripe/raygun
```

## Configuration

Add the following to your `.env` file:

```ini
SS_RAYGUN_APP_KEY="dwhouq3845y98uiof=="
```

If you want to track JavaScript errors in CMS, you can activate `LeftAndMainExtension` in your project YAML configs:

```yml
---
Name: raygunning-left-and-main
---
SilverStripe\Admin\LeftAndMain:
  extensions:
    - SilverStripe\Raygun\LeftAndMainExtension
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

#### Multiple Raygun API Keys (app keys)

You may have more than one Raygun integration, each of which could use custom API keys, different
from the default one (set by `SS_RAYGUN_APP_KEY`). To do so you'll need to configure each one of them separately. Here are some examples:

```yml
# Here's an example of the LeftAndMainExtension using a custom raygun
# API Key, set through a custom environment variable (SS_CUSTOM_RAYGUN_APP_KEY)

---
Name: custom-raygun-leftnmain-extension
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Raygun\LeftAndMainExtension.custom:
    class: SilverStripe\Raygun\LeftAndMainExtension
    properties:
      # You'll have to set the SS_CUSTOM_RAYGUN_APP_KEY environment var
      CustomRaygunAppKey: '`SS_CUSTOM_RAYGUN_APP_KEY`'

---
Name: raygunning-left-and-main
After: custom-raygun-leftnmain-extension
---
SilverStripe\Admin\LeftAndMain:
  extensions:
    - SilverStripe\Raygun\LeftAndMainExtension.custom
```

```yml
# Here's an example of a custom Raygun handler for Monolog
# which uses API Key provided by a custom RaygunClientFactory

---
Name: custom-monolog-raygun-handler
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Raygun\RaygunClientFactory.custom:
    class: SilverStripe\Raygun\RaygunClientFactory
    properties:
      # You'll have to set the SS_CUSTOM_RAYGUN_APP_KEY environment var
      CustomRaygunAppKey: '`SS_CUSTOM_RAYGUN_APP_KEY`'

  Raygun4php\RaygunClient.custom:
    factory: '%$SilverStripe\Raygun\RaygunClientFactory.custom'

  SilverStripe\Raygun\RaygunHandler.custom:
    class: SilverStripe\Raygun\RaygunHandler
    constructor:
      client: '%$Raygun4php\RaygunClient.custom'
      level: 'debug'

  Psr\Log\LoggerInterface:
    calls:
      - [ pushHandler, [ '%$SilverStripe\Raygun\RaygunHandler.custom'] ]
```

#### Proxy

If you need to forward outgoing requests through a proxy (such as for sites hosted in CWP), you can set the proxy host and optional port via yaml config:

```yml
SilverStripe\Core\Injector\Injector:
  Raygun4php\RaygunClient:
    constructor:
      proxyHost: '`SS_OUTBOUND_PROXY`'
      proxyPort: '`SS_OUTBOUND_PROXY_PORT`'
```

## Filtering

Raygun will send the following data:

- $_POST
- $_SERVER
- $_GET (included in URL also)

By default we filter out some sensitive SilverStripe details which appear in the $_SERVER variable. These include:

- SS_DATABASE_USERNAME
- SS_DEFAULT_ADMIN_USERNAME
- SS_RAYGUN_APP_KEY
- Cookie information (through `Cookie` and `HTTP_COOKIE`)
- Basic auth information (through `PHP_AUTH_PW`)
- HTTP authorisation information (through `Authorization` and `HTTP_AUTHORIZATION`)
- Anything containing `PASSWORD`, `KEY`, `SECRET` or `TOKEN` (case insensitive)

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
