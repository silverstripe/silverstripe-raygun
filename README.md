RayGun.io integration for SilverStripe
======================================

This is a simple module that binds RayGun.io to the error & exception handler of SilverStripe.

Configuration
-------------

First, add the composer package as a dependency to your project:

	composer require silverstripe/raygun:*

Then, load in the RayGun application key. Thi is defined in `_ss_environment.php`, like this:

	define('SS_RAYGUN_APP_KEY', 'dwhouq3845y98uiof==');

Alternatively, the API key can be defined in a yaml config file, as well as the minimum level for the error reporting

	RaygunLogWriter:
	  api_key: 'ABCDEF123456=='
	  level: 'SS_Log::WARNING'