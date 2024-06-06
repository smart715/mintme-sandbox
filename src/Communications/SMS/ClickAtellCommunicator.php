<?php declare(strict_types = 1);

namespace App\Communications\SMS;

use App\Communications\GuzzleRestWrapper;
use App\Communications\SMS\Model\SMS;
use Symfony\Component\HttpFoundation\Request;

class ClickAtellCommunicator implements SmsCommunicatorInterface
{
    private GuzzleRestWrapper $guzzleRestWrapper;
    private GuzzleRestWrapper $guzzleRestWrapperUsa;
    private const SEND_SMS_METHOD = 'message';
    private const BALANCE_METHOD = 'balance';
    private const CHANNEL = 'sms';

    public function __construct(GuzzleRestWrapper $guzzleRestWrapper, GuzzleRestWrapper $guzzleRestWrapperUsa)
    {
        $this->guzzleRestWrapper = $guzzleRestWrapper;
        $this->guzzleRestWrapperUsa = $guzzleRestWrapperUsa;
    }

    public function send(SMS $sms): array
    {
        $params = [
            'json' => [
                'messages' => [
                    [
                        'channel' => self::CHANNEL,
                        'to' => $sms->getTo(),
                        'content' => $sms->getContent(),
                    ],
                ],
            ],
        ];

        if (SMS::USA_COUNTRY_CODE === $sms->getCountryCode()) {
            $params['json']['messages'][0]['from'] = SMS::USA_SENT_FROM;
            $response = $this->guzzleRestWrapperUsa->send(self::SEND_SMS_METHOD, Request::METHOD_POST, $params);
        } else {
            $response = $this->guzzleRestWrapper->send(self::SEND_SMS_METHOD, Request::METHOD_POST, $params);
        }

        return json_decode($response, true);
    }

    public function getBalance(): array
    {
        $response = $this->guzzleRestWrapper->send(self::BALANCE_METHOD, Request::METHOD_GET);

        return json_decode($response, true);
    }
}
