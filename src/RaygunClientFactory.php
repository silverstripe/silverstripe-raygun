<?php

namespace SilverStripe\Raygun;

use SilverStripe\Core\Injector\Factory;
use SilverStripe\Security\Member;
use Graze\Monolog\Handler\RaygunHandler;
use Raygun4php\RaygunClient;

class RaygunClientFactory implements Factory
{

    /**
     * The environment variable used to assign the Raygun api key
     *
     * @var string
     */
    private $apiEnvKey = 'SS_RAYGUN_APP_KEY';

    /**
     * Wrapper to get the Raygun API key from the .env file to pass through to
     * the Raygun client.
     *
     * Also set the user to the current member if applicable.
     *
     * {@inheritdoc}
     */
    public function create($service, array $params = [])
    {
        // extract api key from .env file
        $apiKey = getenv($this->apiEnvKey);

        // log error to warn user that exceptions will not be logged to Raygun
        if ($apiKey === false) {
            error_log("You need to set the $this->apiEnvKey environment variable in order to log to Raygun.");
        }

        // setup new client
        $client = new RaygunClient($apiKey);

        // check if there is a current logged in user
        $member = Member::currentUser();

        // by default just set the member email as the user
        if ($member) {
            $client->SetUser($member->Email);
        }

        return $client;
    }

}
