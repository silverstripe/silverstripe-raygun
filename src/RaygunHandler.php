<?php

namespace SilverStripe\Raygun;

use Exception;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Raygun4php\RaygunClient;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Security\Security;
use Throwable;

/**
 * The bulk of this file was originally part of Monolog Extensions
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Nature Delivered Ltd. <http://graze.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @see http://github.com/graze/MonologExtensions/blob/master/LICENSE
 * @link http://github.com/graze/MonologExtensions
 */
class RaygunHandler extends AbstractProcessingHandler
{
    use Configurable;

    private static string $user_main_id_field = 'Email';

    private static bool $user_include_firstname = false;

    private static bool $user_include_fullname = false;

    private static bool $user_include_email = false;

    private static bool $enabled = true;

    protected RaygunClient $client;

    public function __construct(RaygunClient $client, $level = Level::Debug, bool $bubble = true)
    {
        $this->client = $client;

        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        // If not enabled, don't write anything.
        if (!$this->config()->get('enabled')) {
            return;
        }

        // Set user tracking and data.
        $disableTracking = Config::inst()->get(
            RaygunClient::class,
            'disable_user_tracking'
        );
        $disableTracking = is_bool($disableTracking) ? $disableTracking : false;

        if (!$disableTracking) {
            $user = Security::getCurrentUser();

            if ($user) {
                $idField = $this->config()->get('user_main_id_field');
                $this->client->SetUser(
                    (string) $user->$idField,
                    $this->config()->get('user_include_firstname') ? $user->FirstName : null,
                    $this->config()->get('user_include_fullname') ? $user->getName() : null,
                    $this->config()->get('user_include_email') ? $user->Email : null
                );
            }
        }

        // Write exceptions and errors appropriately
        $context = $record->context;
        $formatted = $record->formatted;
        $exception = $context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            $this->writeException(
                $record,
                $formatted['tags'],
                $formatted['custom_data'],
                $formatted['timestamp']
            );
        } elseif (isset($context['file']) && isset($context['line'])) {
            $this->writeError(
                $formatted,
                $formatted['tags'],
                $formatted['custom_data'],
                $formatted['timestamp']
            );
        }

        // do nothing if it's not an exception or an error
    }

    protected function writeError(
        LogRecord $record,
        array $tags = [],
        array $customData = [],
        int|float|null $timestamp = null
    ) {
        $context = $record->context;
        $this->client->SendError(
            0,
            $record->message,
            $context['file'],
            $context['line'],
            $tags,
            $customData,
            $timestamp
        );
    }

    protected function writeException(
        LogRecord $record,
        array $tags = [],
        array $customData = [],
        int|float|null $timestamp = null
    ) {
        $this->client->SendException($record->context['exception'], $tags, $customData, $timestamp);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new RaygunFormatter();
    }
}
