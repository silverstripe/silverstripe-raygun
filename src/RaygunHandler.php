<?php

namespace SilverStripe\Raygun;

use Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;
use Graze\Monolog\Formatter\RaygunFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Raygun4php\RaygunClient;


class RaygunHandler extends AbstractProcessingHandler
{

    /**
     * @var RaygunClient
     */
    protected $client = null;

    /**
     * @var string $factoryClass Factory class used to instantiate a RaygunClient
     */
    protected $factoryClass = null;

    /**
     * @param RaygunClient|null $client
     * @param int $level
     * @param bool $bubble
     */
    public function __construct($client = null, $level = Logger::DEBUG, $bubble = true)
    {
        $this->client = $client;

        parent::__construct($level, $bubble);
    }

    /**
     * Set the the name of the factory class to use for initialisation if not Raygun client is available when writing a
     * record
     * @param string $className
     */
    public function setRaygunClientFactory($className)
    {
        $this->factoryClass = $className;
    }

    /**
     * Set a Raygun client instance to use for logging
     * @param RaygunClient $client
     */
    public function setClient(RaygunClient $client)
    {
        $this->client = $client;
    }

    /**
     * Return the Raygun client instance. If it does not exist instantiate it using the factory class.
     *
     * @return RaygunClient
     * @throws Exception Throws and Exception if no client instance is available and no factory class name was provided
     * for instantiation.
     */
    public function getOrInitClient()
    {
        if (empty($this->client)) {
            if (empty($this->factoryClass)) {
                throw new Exception('No RaygunClient available and no factory class given');
            } else {
                $factory = Injector::inst()->get($this->factoryClass);
                $this->client = $factory->create('RaygunClient');
            }
        }
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        if(parent::isHandling($record)) {
            $context = $record['context'];

            //Ensure only valid records will be handled and no InvalidArgumentException will be thrown
            if ((isset($context['exception']) &&
                    (
                        $context['exception'] instanceof \Exception ||
                        (PHP_VERSION_ID > 70000 && $context['exception'] instanceof \Throwable)
                    )
                ) || (isset($context['file']) && $context['line'])
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Write record after getting or initiating Raygun client
     * Log message if no client is initialized.
     * @param array $record
     */
    protected function write(array $record)
    {
        try {
            $this->getOrInitClient();
            $member = Member::currentUser();
            if ($member) {
                $this->client->SetUser($member->Email);
            }

            $this->doWrite($record);
        } catch (Exception $e) {
            error_log('RaygunClient not instantiated. Messages will not be sent');
        }
    }

    /**
     * Copy write from Graze\Monolog\Handler\RaygunHandler
     * In contrast to the Graze RaygunHandler this removes InvalidArgumentException to conform to PSR-3
     * @param array $record
     */
    protected function doWrite(array $record)
    {
        $context = $record['context'];

        if (isset($context['exception']) &&
            (
                $context['exception'] instanceof \Exception ||
                (PHP_VERSION_ID > 70000 && $context['exception'] instanceof \Throwable)
            )
        ) {
            $this->writeException(
                $record,
                $record['formatted']['tags'],
                $record['formatted']['custom_data'],
                $record['formatted']['timestamp']
            );
        } elseif (isset($context['file']) && $context['line']) {
            $this->writeError(
                $record['formatted'],
                $record['formatted']['tags'],
                $record['formatted']['custom_data'],
                $record['formatted']['timestamp']
            );
        }
    }

    /**
     * Copy writeError from Graze\Monolog\Handler\RaygunHandler
     * @param array $record
     * @param array $tags
     * @param array $customData
     * @param int|float $timestamp
     */
    protected function writeError(array $record, array $tags = array(), array $customData = array(), $timestamp = null)
    {
        $context = $record['context'];
        $this->client->SendError(
            0,
            $record['message'],
            $context['file'],
            $context['line'],
            $tags,
            $customData,
            $timestamp
        );
    }

    /**
     * Copy writeException from Graze\Monolog\Handler\RaygunHandler
     * @param array $record
     * @param array $tags
     * @param array $customData
     * @param int|float $timestamp
     */
    protected function writeException(
        array $record,
        array $tags = array(),
        array $customData = array(),
        $timestamp = null
    ) {
        $this->client->SendException($record['context']['exception'], $tags, $customData, $timestamp);
    }

    /**
     * Copy getDefaultFormatter from Graze\Monolog\Handler\RaygunHandler
     * @return \Monolog\Formatter\FormatterInterface
     */
    protected function getDefaultFormatter()
    {
        return new RaygunFormatter();
    }
}