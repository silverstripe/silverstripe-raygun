RayGun.io integration for SilverStripe
======================================

This is a simple module that binds RayGun.io to the error & exception handler of SilverStripe.

Configuration
-------------

First, add the composer package as a dependency to your project:

	composer require silverstripe/raygun:*

Then, load in the RayGun application key. Thi is defined in `_ss_environment.php`, like this:

	define('SS_RAYGUN_APP_KEY', 'dwhouq3845y98uiof==');

TO DO: Allow definition in project, rather than environment, configuration.