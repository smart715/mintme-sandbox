<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class RabbitMQCommunicator implements RabbitMQCommunicatorInterface
{
    /** @var RestRpcInterface */
    private $rpc;

    public function __construct(RestRpcInterface $rpc)
    {
        $this->rpc = $rpc;
    }

    public function fetchConsumers(): array
    {
        $response = $this->rpc->send("api/consumers", Request::METHOD_GET);

        $response = json_decode($response, true);
        $parsed = [];

        foreach ($response as $consumer) {
            $parsed[$consumer['queue']['name']] = $consumer['queue']['name'];
        }

        return $parsed;
    }
}
