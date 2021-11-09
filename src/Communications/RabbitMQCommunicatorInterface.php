<?php declare(strict_types = 1);

namespace App\Communications;

interface RabbitMQCommunicatorInterface
{
    public function fetchConsumers(): array;
}
