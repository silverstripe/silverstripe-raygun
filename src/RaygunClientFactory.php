<?php

namespace SilverStripe\Raygun;

use SilverStripe\Core\Injector\Factory;
use Raygun4php\RaygunClient;

class RaygunClientFactory implements Factory
{

    /**
     * The environment variable used to assign the Raygun api key
     *
     * @var string
     */
    const RAYGUN_APP_KEY_NAME = 'SS_RAYGUN_APP_KEY';

    /**
     * @var Raygun4php\RaygunClient
     */
    protected $client;

    /**
     * Wrapper to get the Raygun API key from the .env file to pass through to
     * the Raygun client.
     *
     * {@inheritdoc}
     */
    public function create($service, array $params = [])
    {
        // extract api key from .env file
        $apiKey = (string) getenv(self::RAYGUN_APP_KEY_NAME);

        // log error to warn user that exceptions will not be logged to Raygun
        if (empty($apiKey)) {
            $name = self::RAYGUN_APP_KEY_NAME;
            error_log("You need to set the {$name} environment variable in order to log to Raygun.");
        }

        // setup new client
        $this->client = new RaygunClient($apiKey);

        $this->filterSensitiveData();

        return $this->client;
    }

    /**
     * Filter out password authentication information
     *
     * Also filter out any defined server variables, most notably SS_ environment variables
     * that end with either '_USERNAME', '_PASSWORD' or '_KEY', e.g.
     * SS_DATABASE_USERNAME
     * SS_DATABASE_PASSWORD
     * MY_THIRD_PARTY_API_KEY
     *
     * On some hosting providers, variables defined in .env will end up in $_SERVER
     */
    protected function filterSensitiveData()
    {
        $filterParams = [
            'php_auth_pw',
            '/password/i',
            self::RAYGUN_APP_KEY_NAME
        ];
        foreach(array_keys($_SERVER) as $key) {
            $substr9 = substr($key, -9);
            if (substr($key, -4) === '_KEY'
                || $substr9 === '_USERNAME'
                || $substr9 === '_PASSWORD'
            ) {
                $filterParams[] = $key;
            }
        }
        $this->client->setFilterParams($filterParams);
    }

}
