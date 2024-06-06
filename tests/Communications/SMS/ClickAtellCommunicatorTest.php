<?php declare(strict_types = 1);

namespace App\Tests\Communications\SMS;

use App\Communications\GuzzleRestWrapper;
use App\Communications\SMS\ClickAtellCommunicator;
use App\Communications\SMS\Model\SMS;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ClickAtellCommunicatorTest extends TestCase
{
    private const TO = '+447700900000';
    private const CONTENT = 'Test message';
    private const COUNTRY_CODE = '44';

    public function testSend(): void
    {
        $guzzleRestWrapper = $this->mockGuzzleRestWrapper([
            'message',
            Request::METHOD_POST,
            [
                'json' => [
                    'messages' => [
                        [
                            'channel' => 'sms',
                            'to' => self::TO,
                            'content' => self::CONTENT,
                        ],
                    ],
                ],
            ],
        ]);

        $communicator = new ClickAtellCommunicator($guzzleRestWrapper, $guzzleRestWrapper);

        $sms = $this->mockSMS(self::TO, self::CONTENT, self::COUNTRY_CODE);

        $response = $communicator->send($sms);

        $this->assertEquals(['test' => 'test'], $response);
    }

    public function testGetBalance(): void
    {
        $guzzleRestWrapper = $this->mockGuzzleRestWrapper([
            'balance',
            Request::METHOD_GET,
        ]);

        $communicator = new ClickAtellCommunicator($guzzleRestWrapper, $guzzleRestWrapper);

        $response = $communicator->getBalance();

        $this->assertEquals(['test' => 'test'], $response);
    }

    private function mockGuzzleRestWrapper(array $withData): GuzzleRestWrapper
    {
        $guzzleWrapper = $this->createMock(GuzzleRestWrapper::class);
        $guzzleWrapper->expects($this->once())
            ->method('send')
            ->with(...$withData)
            ->willReturn(json_encode(['test' => 'test']));

        return $guzzleWrapper;
    }

    private function mockSMS(string $to, string $content, string $countryCode): SMS
    {
        $sms = $this->createMock(SMS::class);
        $sms->method('getTo')->willReturn($to);
        $sms->method('getContent')->willReturn($content);
        $sms->method('getCountryCode')->willReturn($countryCode);

        return $sms;
    }
}
