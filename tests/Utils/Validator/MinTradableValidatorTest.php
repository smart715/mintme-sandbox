<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\TradableInterface;
use App\Exchange\Market;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Validator\MinTradableValidator;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MinTradableValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        string $amount,
        ?string $minimum,
        bool $isValid,
        string $message
    ): void {
        $validator = new MinTradableValidator(
            $this->mockTradable(),
            $this->createMock(Market::class),
            $amount,
            $minimum,
            $this->mockMoneyWrapper($minimum, $amount, $isValid),
            $this->mockTranslator($isValid),
            $this->mockRebrandingConverter($isValid)
        );

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals($message, $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "valid if amount is larger than minimum" => [
                "amount" => "100",
                "minimum" => "50",
                "isValid" => true,
                "message" => '',
            ],
            "valid if amount equals minimum" => [
                "amount" => "1",
                "minimum" => "1",
                "isValid" => true,
                "message" => '',
            ],
            "valid if minimum does not exist" => [
                "amount" => "1",
                "minimum" => null,
                "isValid" => true,
                "message" => '',
            ],
            "invalid if amount is smaller than minimum" => [
                "amount" => "1",
                "minimum" => "2",
                "isValid" => false,
                "message" => 'test',
            ],
        ];
    }

    private function mockTradable(): TradableInterface
    {
        $tradable = $this->createMock(TradableInterface::class);
        $tradable->method('getMoneySymbol')->willReturn('TEST');
        $tradable->method('getShowSubunit')->willReturn(4);

        return $tradable;
    }

    private function mockMoneyWrapper(
        ?string $minimum,
        string $amount,
        bool $isValid
    ): MoneyWrapperInterface {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->method('parse')->willReturnOnConsecutiveCalls(
            $this->dummyMoneyObject($minimum),
            $this->dummyMoneyObject($amount),
        );

        $moneyWrapper->expects($isValid ? $this->never() : $this->once())
            ->method('format');

        return $moneyWrapper;
    }

    private function mockTranslator(bool $isValid): translatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($isValid ? $this->never() : $this->once())
            ->method('trans')
            ->willReturn('test');

        return $translator;
    }

    private function mockRebrandingConverter(bool $isValid): RebrandingConverterInterface
    {
        $rebrandingConverter = $this->createMock(RebrandingConverterInterface::class);
        $rebrandingConverter->expects($isValid ? $this->never() : $this->once())
        ->method('convert');

        return $rebrandingConverter;
    }

    private function dummyMoneyObject(?string $amount): Money
    {
        return new Money(
            $amount,
            new Currency('TEST')
        );
    }
}
