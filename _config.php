<?php

$raygunAPIKey = Config::inst()->get('RaygunLogWriter', 'api_key');
if(empty($raygunAPIKey) && defined('SS_RAYGUN_APP_KEY')) {
	$raygunAPIKey = SS_RAYGUN_APP_KEY;
}

if(!empty($raygunAPIKey)) {
	$raygun = new RaygunLogWriter($raygunAPIKey);
	SS_Log::add_writer($raygun);

	register_shutdown_function(array($raygun, 'shutdown_function'));

} else {
	if(Director::isLive()) {
		user_error("SilverStripe RayGun module installed, but SS_RAYGUN_APP_KEY not defined in _ss_environment.php", E_USER_WARNING);
	}
}
