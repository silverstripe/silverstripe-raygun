<?php

namespace SilverStripe\Raygun;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

/**
 * This file was originally part of Monolog Extensions
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
class RaygunFormatter extends NormalizerFormatter
{
    /**
     * {@inheritdoc}
     *
     * @param LogRecord $record A record to format
     * @return mixed The formatted record
     */
    public function format(LogRecord $record)
    {
        $record = parent::format($record);

        $record['tags'] = [];
        $record['custom_data'] = [];
        $record['timestamp'] = null;

        foreach (['extra', 'context'] as $source) {
            if (array_key_exists('tags', $record[$source]) && is_array($record[$source]['tags'])) {
                $record['tags'] = array_merge($record['tags'], $record[$source]['tags']);
            }

            if (array_key_exists('timestamp', $record[$source]) && is_numeric($record[$source]['timestamp'])) {
                $record['timestamp'] = $record[$source]['timestamp'];
            }

            unset($record[$source]['tags'], $record[$source]['timestamp']);
        }

        $record['custom_data'] = $record['extra'];
        $record['extra'] = [];

        foreach ($record['context'] as $key => $item) {
            if (!in_array($key, ['file', 'line', 'exception'])) {
                $record['custom_data'][$key] = $item;
                unset($record['context'][$key]);
            }
        }

        return $record;
    }
}
