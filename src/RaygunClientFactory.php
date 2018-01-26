<?php

namespace SilverStripe\Raygun;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Factory;
use Raygun4php\RaygunClient;

class RaygunClientFactory implements Factory
{

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
        $apiKey = Environment::getEnv('SS_RAYGUN_APP_KEY');

        // log error to warn user that exceptions will not be logged to Raygun
        if (empty($apiKey)) {
            $name = Environment::getEnv('SS_RAYGUN_APP_KEY');
            error_log("You need to set the {$name} environment variable in order to log to Raygun.");
        }

        // setup new client
        $this->client = new RaygunClient($apiKey);

        $this->filterSensitiveData();

        return $this->client;
    }

    protected function filterSensitiveData()
    {
        // Filter sensitive data out of server variables
        $this->client->setFilterParams([
            'SS_DATABASE_USERNAME' => true,
            'SS_DATABASE_PASSWORD' => true,
            'SS_DEFAULT_ADMIN_USERNAME' => true,
            'SS_DEFAULT_ADMIN_PASSWORD' => true,
            Environment::getEnv('SS_RAYGUN_APP_KEY') => true,
        ]);
    }
}
