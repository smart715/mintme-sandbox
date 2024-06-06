<?php declare(strict_types = 1);

namespace App\Tests\Communications\SMS;

use App\Communications\GuzzleRestWrapper;
use App\Communications\SMS\D7NetworksCommunicator;
use App\Communications\SMS\Model\SMS;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class D7NetworksCommunicatorTest extends TestCase
{
    private const SEND_SMS_METHOD = 'send';
    private const SMS_GET_BALANCE = 'balance';
    private const FROM = '+447700900000';
    private const TO = '+447700910000';
    private const CONTENT = 'Test message';

    public function testSend(): void
    {
        $message =  [
            'channel' => 'sms',
            'msg_type' => 'text',
            'recipients' => [self::TO],
            'content' => self::CONTENT,
            'data_coding' => 'auto',
        ];

        $data = [
            'json'=> [
                'messages' => [
                    $message,
                ],
                'message_globals' => [
                    'originator' => self::FROM,
                ],
            ],
        ];

        $guzzleRestWrapper = $this->mockGuzzleRestWrapper([
            self::SEND_SMS_METHOD,
            Request::METHOD_POST,
            $data,
        ]);

        $communicator = new D7NetworksCommunicator($guzzleRestWrapper);
        $response = $communicator->send($this->mockSMS(self::FROM, self::TO, self::CONTENT));

        $this->assertEquals(['test' => 'test'], $response);
    }

    public function testGetBalance(): void
    {
        $guzzleRestWrapper = $this->mockGuzzleRestWrapper([
            self::SMS_GET_BALANCE,
            Request::METHOD_GET,
        ]);

        $communicator = new D7NetworksCommunicator($guzzleRestWrapper);

        $response = $communicator->getBalance();

        $this->assertEquals(['test' => 'test'], $response);
    }

    private function mockGuzzleRestWrapper(array $withData): GuzzleRestWrapper
    {
        $guzzleWrapper = $this->createMock(GuzzleRestWrapper::class);
        $guzzleWrapper->expects($this->at(0))
            ->method('send')
            ->with(...$withData)
            ->willReturn(json_encode(['test' => 'test']));

        return $guzzleWrapper;
    }

    private function mockSMS(string $from, string $to, string $content): SMS
    {
        $sms = $this->createMock(SMS::class);
        $sms->method('getFrom')->willReturn($from);
        $sms->method('getTo')->willReturn($to);
        $sms->method('getContent')->willReturn($content);

        return $sms;
    }
}
