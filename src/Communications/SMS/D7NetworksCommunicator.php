<?php declare(strict_types = 1);

namespace App\Communications\SMS;

use App\Communications\RestRpcInterface;
use App\Communications\SMS\Model\SMS;
use Symfony\Component\HttpFoundation\Request;

class D7NetworksCommunicator implements SmsCommunicatorInterface
{
    public const SEND_SMS_METHOD = 'send';
    public const SMS_GET_BALANCE = 'balance';

    private RestRpcInterface $rpc;

    public function __construct(RestRpcInterface $rpc)
    {
        $this->rpc = $rpc;
    }

    public function send(SMS $sms): array
    {
        $message = [
            'channel' => 'sms',
            'msg_type' => 'text',
            'recipients' => [$sms->getTo()],
            'content' => $sms->getContent(),
            'data_coding' => 'auto',
        ];

        $response = $this->rpc->send(
            self::SEND_SMS_METHOD,
            Request::METHOD_POST,
            [
                'json' => [
                    'messages' => [
                        $message,
                    ],
                    'message_globals' => [
                        'originator' => $sms->getFrom(),
                    ],
                ],
            ]
        );

        return json_decode($response, true);
    }

    public function getBalance(): array
    {
        $response = $this->rpc->send(
            self::SMS_GET_BALANCE,
            Request::METHOD_GET,
            []
        );

        return json_decode($response, true);
    }
}
