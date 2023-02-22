<?php

namespace SilverStripe\Raygun;

use GuzzleHttp\Client;
use LogicException;
use Psr\SimpleCache\CacheInterface;
use Raygun4php\RaygunClient;
use Raygun4php\Transports\GuzzleAsync;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Factory;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Path;

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
    public function create($service, array $params = [])
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

        // Setup new client in the way that is best for the current SDK version.
        if (substr(ltrim(static::getSdkVersion(), 'v'), 0, 2) === '1.') {
            $this->createForV1($apiKey, $disableTracking, $params);
        } else {
            $this->createForV2($apiKey, $disableTracking, $params);
        }

        $this->filterSensitiveData();

        return $this->client;
    }

    protected function createForV1($apiKey, $disableTracking, $params)
    {
        // Instantiate actual client
        $this->client = new RaygunClient(
            $apiKey,
            true,
            false,
            $disableTracking
        );

        // Set proxy
        if (!empty($params['proxyHost'])) {
            $proxy = $params['proxyHost'];

            if (!empty($params['proxyPort'])) {
                $proxy .= ':' . $params['proxyPort'];
            }

            $this->client->setProxy($proxy);
        }
    }

    protected function createForV2($apiKey, $disableTracking, $params)
    {
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

    /**
     * Get the currently installed version of the raygun4php package according to composer.lock
     *
     * @return string
     */
    public static function getSdkVersion()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.raygunCache');
        /** @var CoreKernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);

        // If the SDK version isn't cached, get it from the composer.lock file.
        // Note that this is called before flushing has occurred - if we're flushing, bypass the cache for now.
        if ($kernel->isFlushed() || !$version = $cache->get('raygun4phpVersion')) {
            $composerLockRaw = file_get_contents(Path::join(Director::baseFolder(), 'composer.lock'));

            if (!$composerLockRaw) {
                throw new LogicException('composer.lock file is missing.');
            }

            $packageList = json_decode($composerLockRaw, true)['packages'];

            foreach ($packageList as $package) {
                if ($package['name'] === 'mindscape/raygun4php') {
                    $version = $package['version'];
                    break;
                }
            }

            if (!$version) {
                throw new LogicException('mindscape/raygun4php not found in composer.lock');
            }

            // Cache the SDK version so we don't have to do this every request.
            $cache->set('raygun4phpVersion', $version);
        }

        return $version;
    }

    private static function isManifestFlushed(): bool
    {
        $kernel = Injector::inst()->get(Kernel::class);

        // Only CoreKernel implements this method at the moment
        // Introducing it to the Kernel interface is a breaking change
        if (method_exists($kernel, 'isFlushed')) {
            return $kernel->isFlushed();
        }

        $classManifest = $kernel->getClassLoader()->getManifest();

        return $classManifest->isFlushed();
    }

    public static function flush()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.raygunCache');
        $cache->clear();
    }
}
