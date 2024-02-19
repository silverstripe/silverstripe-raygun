<?php
namespace SilverStripe\Raygun\Test;

use Cassandra\Time;
use Exception;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\SimpleCache\CacheInterface;
use Raygun4php\RaygunClient;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Raygun\RaygunClientFactory;
use SilverStripe\Raygun\RaygunFormatter;
use SilverStripe\Raygun\RaygunHandler;
use SilverStripe\Security\Security;
use Throwable;
use DateTime;
use Monolog\DateTimeImmutable;

class RaygunHandlerTest extends SapphireTest
{
    public function testEnabledLimitConfig(): void
    {
        // Get instance of raygun handler
        $raygun = $this->getRaygunHandler();

        // Enable limit events
        $raygun::config()->set('enabled_limit', true);

        // Check that only first event allowed
        $this->assertTrue($raygun->isEnabled());
        $this->assertFalse($raygun->isEnabled());

        // Disable limit events
        $raygun::config()->set('enabled_limit', false);

        // Check that multiple events allowed
        $this->assertTrue($raygun->isEnabled());
        $this->assertTrue($raygun->isEnabled());

        // Enable limit events
        $raygun::config()->set('enabled_limit', true);

        // Check that events not allowed as first one is sent
        $this->assertFalse($raygun->isEnabled());

        // Clear cache
        Injector::inst()->get(CacheInterface::class . '.raygunCache')->clear();

        // Check that only first event allowed
        $this->assertTrue($raygun->isEnabled());
        $this->assertFalse($raygun->isEnabled());
    }

    public function testReportFrequencyConfig(): void
    {
        // Clear cache
        Injector::inst()->get(CacheInterface::class . '.raygunCache')->clear();

        // Get instance of raygun handler
        $raygun = $this->getRaygunHandler();

        // Enable report frequency
        $raygun::config()->set('report_frequency', 300);

        // Check that only first event allowed
        $this->assertTrue($raygun->isEnabled());
        $this->assertFalse($raygun->isEnabled());

        // Mock old record pass 5 minutes
        $date = (new DateTimeImmutable('now'))->sub(new \DateInterval('PT301S'));
        $raygun->record = $this->getRecord(Level::Warning, 'warning', [], 'test', $date);

        // Clear cache
        Injector::inst()->get(CacheInterface::class . '.raygunCache')->clear();

        // Check that multiple events allowed and record cached with date 300 second old
        $this->assertTrue($raygun->isEnabled());

        // New record with current date
        $raygun->record = $this->getRecord(Level::Warning, 'warning');

        // Check that event is allowed
        $this->assertTrue($raygun->isEnabled());
    }

    protected function getRaygunHandler(): RaygunHandler
    {
        $client = Injector::inst()->get(RaygunClientFactory::class)->create(null);
        $raygun = new class($client) extends RaygunHandler implements TestOnly
        {
            public ?LogRecord $record = null;

            public function isEnabled(): bool
            {
                return $this->isValidError($this->record);
            }
        };

        $raygun->record = $this->getRecord(Level::Warning, 'warning');

        return $raygun;
    }

    protected function getRecord(int|string|Level $level = Level::Warning, string|\Stringable $message = 'test', array $context = [], string $channel = 'test', \DateTimeImmutable $datetime = new DateTimeImmutable(true), array $extra = []): LogRecord
    {
        return new LogRecord(
            message: (string) $message,
            context: $context,
            level: Logger::toMonologLevel($level),
            channel: $channel,
            datetime: $datetime,
            extra: $extra,
        );
    }

}
