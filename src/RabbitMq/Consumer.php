<?php declare(strict_types = 1);

namespace App\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\Consumer as BaseConsumer;

class Consumer extends BaseConsumer
{
    public function reconnect(): void
    {
        if ($this->conn->isConnected()) {
            return;
        }

        $this->conn->reconnect();
    }
}
