<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Donation;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Exchange\Config\Config;
use App\Exchange\Donation\DonationFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DonationFetcherTest extends TestCase
{
    /** @dataProvider getCheckDonationProvider */
    public function testCheckDonation(bool $hasError, ?array $expectedToReceive): void
    {
        $jsonResponse = $this->createMock(JsonRpcResponse::class);
        $jsonResponse->method('hasError')->willReturn($hasError);
        $jsonResponse->method('getResult')->willReturn($expectedToReceive);

        $jsonRpc = $this->createMock(JsonRpcInterface::class);
        $jsonRpc->method('send')
            ->with('order.check_donation', [
                'TOK000000000123WEB',
                '25',
                '0.01',
                2,
            ])
            ->willReturn($jsonResponse);

        $donationFetcher = new DonationFetcher($jsonRpc, $this->mockConfig(0));

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $checkDonationRawResult = $donationFetcher->checkDonation('TOK000000000123WEB', '25', '0.01', 2);

        $this->assertEquals(
            $expectedToReceive[0],
            $checkDonationRawResult->getExpectedTokens()
        );

        $this->assertEquals(
            $expectedToReceive[1],
            $checkDonationRawResult->getTokensWorth()
        );
    }

    public function getCheckDonationProvider(): array
    {
        return [
            [
                false,
                [
                    '18',
                    '12.7',
                ],
            ],
            [true, null],
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
                5,
                'TOK000000000123BTC',
                '37.45',
                '0.01',
                '28.9',
                1,
            ])
            ->willReturn($jsonResponse);

        $donationFetcher = new DonationFetcher($jsonRpc, $this->mockConfig(0));

        if ($hasError) {
            $this->expectException(FetchException::class);
        }

        $donationFetcher->makeDonation(5, 'TOK000000000123BTC', '37.45', '0.01', '28.9', 1);
        $this->assertTrue(true);
    }

    public function getMakeDonationProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    /** @return Config|MockObject */
    private function mockConfig(int $offset): Config
    {
        $config = $this->createMock(Config::class);

        $config->method('getOffset')->willReturn($offset);

        return $config;
    }
}
