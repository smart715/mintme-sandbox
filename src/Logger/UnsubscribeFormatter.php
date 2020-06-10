<?php declare(strict_types = 1);

namespace App\Logger;

use Monolog\Formatter\FormatterInterface;

class UnsubscribeFormatter implements FormatterInterface
{
    /**
     * {@inheritDoc}
     */
    public function format(array $record)
    {
        return $record['message'];
    }

    /**
     * {@inheritDoc}
     */
    public function formatBatch(array $records)
    {
        return array_map(function ($record) {
            return $record['message'];
        }, $records);
    }
}
