<?php

namespace SilverStripe\Raygun;

use SilverStripe\Core\Injector\Factory;
use SilverStripe\Core\Environment;
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
        $apiKey = (string) Environment::getEnv(self::RAYGUN_APP_KEY_NAME);

        // log error to warn user that exceptions will not be logged to Raygun
        if (empty($apiKey)) {
            $name = self::RAYGUN_APP_KEY_NAME;
            user_error("You need to set the {$name} environment variable in order to log to Raygun.", E_USER_WARNING);
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
            '/SS_DATABASE_USERNAME/' => true,
            '/SS_DATABASE_PASSWORD/' => true,
            '/SS_DEFAULT_ADMIN_USERNAME/' => true,
            '/SS_DEFAULT_ADMIN_PASSWORD/' => true,
            sprintf('/%s/', self::RAYGUN_APP_KEY_NAME) => true,
            '/HTTP_AUTHORIZATION/' => true,
            '/PHP_AUTH_PW/' => true,
            '/HTTP_COOKIE/' => true,
            'Authorization' => true,
            'Cookie' => true,
        ]);
    }

}
