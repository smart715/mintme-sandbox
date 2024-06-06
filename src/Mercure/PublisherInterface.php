<?php declare(strict_types = 1);

namespace App\Mercure;

interface PublisherInterface
{
    /**
     * @param string $topic
     * @param mixed $payload
     */
    public function publish(string $topic, $payload, bool $private = false): void;
}
