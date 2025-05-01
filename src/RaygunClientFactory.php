<?php

namespace SilverStripe\Raygun;

use GuzzleHttp\Client;
use Psr\SimpleCache\CacheInterface;
use Raygun4php\RaygunClient;
use Raygun4php\Transports\GuzzleAsync;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Factory;
use SilverStripe\Core\Injector\Injector;

class RaygunClientFactory implements Factory, Flushable
{
    use CustomAppKeyProvider;

    /**
     * The environment variable used to assign the Raygun api key
     *
     * @var string
     */
    const RAYGUN_APP_KEY_NAME = 'SS_RAYGUN_APP_KEY';

    /**
     * @var RaygunClient
     */
    protected $client;

    /**
     * Wrapper to get the Raygun API key from the .env file to pass through to
     * the Raygun client.
     *
     * {@inheritdoc}
     */
    public function create(string $service, array $params = []): ?object
    {
        // extract api key from .env file
        $apiKey = $this->getCustomRaygunAppKey() ?? (string) Environment::getEnv(self::RAYGUN_APP_KEY_NAME);

        // log error to warn user that exceptions will not be logged to Raygun
        if (empty($apiKey) && !Director::isDev()) {
            $name = self::RAYGUN_APP_KEY_NAME;
            user_error("You need to set the {$name} environment variable in order to log to Raygun.", E_USER_WARNING);
        }

        // check if user tracking is enabled
        $disableTracking = Config::inst()->get(
            RaygunClient::class,
            'disable_user_tracking'
        );
        $disableTracking = is_bool($disableTracking) ? $disableTracking : false;

        // Prepare transport config.
        $transportConfig = [
            'base_uri' => 'https://api.raygun.com',
            'timeout' => 2.0,
            'headers' => [
                'X-ApiKey' => $apiKey,
            ],
        ];

        // Set proxy
        if (!empty($params['proxyHost'])) {
            $proxy = $params['proxyHost'];

            if (!empty($params['proxyPort'])) {
                $proxy .= ':' . $params['proxyPort'];
            }

            $transportConfig['proxy'] = $proxy;
        }

        // Create raygun client using async transport.
        $transport = new GuzzleAsync(
            new Client($transportConfig)
        );
        $this->client = new RaygunClient($transport, $disableTracking);

        // Ensure asynchronous requests are given time to finish.
        register_shutdown_function([$transport, 'wait']);

        $this->filterSensitiveData();

        return $this->client;
    }

    protected function filterSensitiveData()
    {
        // Filter sensitive data out of server variables
        $this->client->setFilterParams([
            '/SS_DATABASE_USERNAME/' => true,
            '/SS_DEFAULT_ADMIN_USERNAME/' => true,
            '/KEY/i' => true,
            '/TOKEN/i' => true,
            '/PASSWORD/i' => true,
            '/SECRET/i' => true,
            sprintf('/%s/', self::RAYGUN_APP_KEY_NAME) => true,
            '/HTTP_AUTHORIZATION/' => true,
            '/PHP_AUTH_PW/' => true,
            '/HTTP_COOKIE/' => true,
            'Authorization' => true,
            'Cookie' => true,
        ]);
    }

    public static function flush()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.raygunCache');
        $cache->clear();
    }
}
