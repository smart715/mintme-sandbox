<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Exchange\Donation\DonationFetcher;
use PHPUnit\Framework\TestCase;

class DonationFetcherTest extends TestCase
{
    /** @dataProvider getCheckDonationProvider */
    public function testCheckDonation(bool $hasError, ?string $expectedToReceive): void
    {
        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($expectedToReceive);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with('order.check_donation', [
                'TOK000000000123WEB',
                '75',
                '1',
            ])
            ->willReturn($jsonResponse);

        $donationFetcher = new DonationFetcher($jsonRpc);

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $this->assertEquals(
            $expectedToReceive,
            $donationFetcher->checkDonation('TOK000000000123WEB', '75', '1')
        );
    }

    public function getCheckDonationProvider(): array
    {
        return [
            [false, '0'],
            [true, '50'],
        ];
    }

    /** @dataProvider getMakeDonationProvider */
    public function testMakeDonation(bool $hasError): void
    {
        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult');

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with('order.make_donation', [
                'TOK000000000123BTC',
                '375000000000',
                '1',
                '20000',
            ])
            ->willReturn($jsonResponse);

        $donationFetcher = new DonationFetcher($jsonRpc);

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $donationFetcher->makeDonation('TOK000000000123BTC', '375000000000', '1', '20000');
        $this->assertTrue(true);
    }

    public function getMakeDonationProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }
}
