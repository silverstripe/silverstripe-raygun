<?php

namespace SilverStripe\Raygun;

use Raygun4php\RaygunClient;
use SilverStripe\Core\Config\Config;
use Graze\Monolog\Handler\RaygunHandler as MonologRaygunHandler;
use SilverStripe\Security\Security;

class RaygunHandler extends MonologRaygunHandler
{
    protected function write(array $record)
    {
        $disableTracking = Config::inst()->get(
            RaygunClient::class,
            'disable_user_tracking'
        );
        $disableTracking = is_bool($disableTracking) ? $disableTracking : false;

        if (!$disableTracking) {
            $user = Security::getCurrentUser();
            if ($user) {
                $this->client->SetUser($user->Email);
            }
        }

        parent::write($record);
    }
}
