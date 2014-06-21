<?php

$raygunAPIKey = Config::inst()->get('RaygunLogWriter', 'api_key');
if(empty($raygunAPIKey) && defined('SS_RAYGUN_APP_KEY')) {
	$raygunAPIKey = SS_RAYGUN_APP_KEY;
}

if(!empty($raygunAPIKey)) {
	$raygun = Injector::inst()->create('RaygunLogWriter', $raygunAPIKey);
	$levelConfig = Config::inst()->get('RaygunLogWriter', 'level');
	$level = defined($levelConfig) ? constant($levelConfig) : SS_Log::WARN;
	SS_Log::add_writer($raygun, $level, '<=');
	register_shutdown_function(array($raygun, 'shutdown_function'));
} else {
	if(Director::isLive()) {
		user_error("SilverStripe RayGun module installed, but SS_RAYGUN_APP_KEY not defined in _ss_environment.php", E_USER_WARNING);
	}
}