<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Communications\CryptoRatesFetcherInterface;
use App\Communications\Exception\FetchException;
use App\Entity\TradableInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Validator\MinUsdValidator;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MinUsdValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(string $amount, string $minimum, bool $isValid): void
    {
        $validator = new MinUsdValidator(
            $this->mockTradable(),
            $amount,
            $minimum,
            $this->mockMoneyWrapper($minimum, $amount),
            $this->mockCryptoRatesFetcher(),
            $this->mockTranslator(),
            $this->mockRebrandingConverter(),
            []
        );

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals('test', $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "valid when amount is greater than minimum" => [
                "amount" => "2",
                "minimum" => "1",
                "isValid" => true,
            ],
            "valid when it's equal" => [
                "amount" => "1",
                "minimum" => "1",
                "isValid" => true,
            ],
            "invalid when amount is smaller" => [
                "amount" => "21",
                "minimum" => "22",
                "isValid" => false,
            ],
        ];
    }

    public function testValidWithException(): void
    {
        $amount = '1';
        $minimum = '1';

        $cryptoRatesFetcher = $this->createMock(CryptoRatesFetcherInterface::class);
        $cryptoRatesFetcher
            ->method('fetch')
            ->willThrowException(new FetchException());


        $this->expectException(FetchException::class);

        $validator = new MinUsdValidator(
            $this->mockTradable(),
            $amount,
            $minimum,
            $this->mockMoneyWrapper($minimum, $amount, true),
            $cryptoRatesFetcher,
            $this->mockTranslator(true),
            $this->mockRebrandingConverter(true),
            []
        );

        $validator->validate();
    }

    private function mockTradable(): TradableInterface
    {
        $tradable = $this->createMock(TradableInterface::class);
        $tradable->method('getMoneySymbol')->willReturn('TEST');
        $tradable->method('getSymbol')->willReturn('TEST');

        return $tradable;
    }

    private function mockMoneyWrapper(
        string $minimum,
        string $amount,
        bool $hasException = false
    ): MoneyWrapperInterface {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper
            ->method('parse')
            ->willReturnOnConsecutiveCalls(
                $this->dummyMoneyObject($amount),
                $this->dummyMoneyObject("9999"),
                $this->dummyMoneyObject($minimum)
            );

        $moneyWrapper
            ->expects($hasException ? $this->never() : $this->once())
            ->method('format')
            ->willReturn("1");

        $moneyWrapper
            ->expects($hasException ? $this->never() : $this->once())
            ->method('convert')
            ->willReturn($this->dummyMoneyObject($amount));

        return $moneyWrapper;
    }

    private function mockTranslator(bool $hasException = false): translatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($hasException ? $this->never() : $this->once())
            ->method('trans')
            ->willReturn('test');

        return $translator;
    }

    private function mockRebrandingConverter(bool $hasException = false): RebrandingConverterInterface
    {
        $rebrandingConverter = $this->createMock(RebrandingConverterInterface::class);
        $rebrandingConverter
            ->expects($hasException ? $this->never() : $this->once())
            ->method('convert');

        return $rebrandingConverter;
    }

    private function dummyMoneyObject(string $amount): Money
    {
        return new Money(
            $amount,
            new Currency('TEST')
        );
    }

    private function mockCryptoRatesFetcher(): CryptoRatesFetcherInterface
    {
        $cryptoRatesFetcher = $this->createMock(CryptoRatesFetcherInterface::class);
        $cryptoRatesFetcher
            ->method('fetch')
            ->willReturn(["TEST" => ["USD"=>1]]);

        return $cryptoRatesFetcher;
    }
}
