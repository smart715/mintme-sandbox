<?php declare(strict_types = 1);

namespace App\Communications;

use Symfony\Component\HttpFoundation\Request;

class D7NetworksCommunicator implements D7NetworksCommunicatorInterface
{
    public const SEND_SMS_METHOD = 'send';

    private RestRpcInterface $rpc;

    public function __construct(RestRpcInterface $rpc)
    {
        $this->rpc = $rpc;
    }

    public function send(string $content): array
    {
        $response = $this->rpc->send(
            self::SEND_SMS_METHOD,
            Request::METHOD_POST,
            [
                'content' => $content,
            ]
        );

        $response = json_decode($response, true);

        return $response;
    }
}
