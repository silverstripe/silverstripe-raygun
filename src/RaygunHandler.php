<?php

namespace SilverStripe\Raygun;

use SilverStripe\Security\Member;

use Graze\Monolog\Handler\RaygunHandler as MonologRaygunHandler;

class RaygunHandler extends MonologRaygunHandler
{
    protected function write(array $record)
    {
        $member = Member::currentUser();
        if ($member) {
            $this->client->SetUser($member->Email);
        }

        parent::write($record);
    }
}
