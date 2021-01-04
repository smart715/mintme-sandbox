<?php declare(strict_types = 1);

namespace App\Communications\SMS;

use App\Communications\RestRpcInterface;
use App\Communications\SMS\Model\SMS;
use Symfony\Component\HttpFoundation\Request;

class D7NetworksCommunicator implements D7NetworksCommunicatorInterface
{
    public const SEND_SMS_METHOD = 'send';

    private RestRpcInterface $rpc;

    public function __construct(RestRpcInterface $rpc)
    {
        $this->rpc = $rpc;
    }

    public function send(SMS $sms): array
    {
        $response = $this->rpc->send(
            self::SEND_SMS_METHOD,
            Request::METHOD_POST,
            [
                'json' =>
                    [
                        'from' => $sms->getFrom(),
                        'to' => $sms->getTo(),
                        'content' => $sms->getContent(),
                    ],
            ]
        );

        $response = json_decode($response, true);

        return $response;
    }
}
