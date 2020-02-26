<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Exchange\Donation\DonationHandler;
use PHPUnit\Framework\TestCase;

class DonationHandlerTest extends TestCase
{
    /** @dataProvider getCheckDonationProvider */
    public function testCheckDonation(bool $hasError, ?string $expectedToReceive): void
    {
        $method = 'order.check_donation';
        $params = ['WEB', '75', '1'];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($expectedToReceive);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $donationHandler = new DonationHandler($jsonRpc);

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $this->assertEquals(
            $expectedToReceive,
            $donationHandler->checkDonation('WEB', '75', '1')
        );
    }

    public function getCheckDonationProvider(): array
    {
        return [
            [false, ''],
            [true, '50'],
        ];
    }

    /** @dataProvider getMakeDonationProvider */
    public function testMakeDonation(bool $hasError): void
    {
        $method = 'order.make_donation';
        $params = ['BTC', '0.003', '1', '0.0003'];

        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult');

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with($this->equalTo($method), $this->equalTo($params))
            ->willReturn($jsonResponse);

        $donationHandler = new DonationHandler($jsonRpc);

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $donationHandler->makeDonation('BTC', '0.003', '1', '0.0003');
    }

    public function getMakeDonationProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }
}
