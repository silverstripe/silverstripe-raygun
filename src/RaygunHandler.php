<?php

namespace SilverStripe\Raygun;

use Raygun4php\RaygunClient;
use SilverStripe\Core\Config\Config;
use Graze\Monolog\Handler\RaygunHandler as MonologRaygunHandler;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Security;

class RaygunHandler extends MonologRaygunHandler
{
    use Configurable;

    private static $user_main_id_field = 'Email';

    private static $user_include_firstname = false;

    private static $user_include_fullname = false;

    private static $user_include_email = false;
	
    private static $enabled = true;

    protected function write(array $record)
    {
        if (!(bool)$this->config()->get('enabled')) {
            return;
        }

        $disableTracking = Config::inst()->get(
            RaygunClient::class,
            'disable_user_tracking'
        );
        $disableTracking = is_bool($disableTracking) ? $disableTracking : false;

        if (!$disableTracking) {
            $user = Security::getCurrentUser();
            if ($user) {
                $idField = $this->config()->get('user_main_id_field');
                $this->client->SetUser(
                    (string)$user->$idField,
                    (bool)$this->config()->get('user_include_firstname') ? $user->FirstName : null,
                    (bool)$this->config()->get('user_include_fullname') ? $user->getName() : null,
                    (bool)$this->config()->get('user_include_email') ? $user->Email : null
                );
            }
        }

        parent::write($record);
    }
}
