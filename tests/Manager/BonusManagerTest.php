<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Manager\BonusManager;
use App\Repository\BonusRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BonusManagerTest extends TestCase
{
    /**
     * @dataProvider isLimitReachedDataProvider
     */
    public function testIsLimitReached(
        string $limit,
        string $totalBonus,
        bool $expected
    ): void {
        $type = 'TEST';

        $bonusRepository = $this->mockBonusRepository();
        $bonusRepository
            ->expects($this->once())
            ->method('getPaidSum')
            ->with($type)
            ->willReturn($totalBonus);

        $bonusManager = new BonusManager(
            $bonusRepository,
            $this->mockMoneyWrapper($limit, $totalBonus)
        );

        $this->assertEquals($expected, $bonusManager->isLimitReached($limit, $type));
    }

    public function isLimitReachedDataProvider(): array
    {
        return [
            'limit equals paid sum results in true' => [
                'limit' => '1',
                'returnValue' => '1',
                'expected' => true,
            ],
            'limit greater than paid sum results in false' => [
                'limit' => '2',
                'returnValue' => '1',
                'expected' => false,
            ],
            'limit smaller than paid sum results in true' => [
                'limit' => '1',
                'returnValue' => '2',
                'expected' => true,
            ],
        ];
    }

    /**
     * @return BonusRepository|MockObject
     */
    private function mockBonusRepository(): BonusRepository
    {
        return $this->createMock(BonusRepository::class);
    }

    private function mockMoneyWrapper(string $amount, string $totalBonus): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper
            ->method('parse')
            ->willReturn(new Money($amount, new Currency('WEB')));
        $moneyWrapper
            ->method('convertToDecimalIfNotation')
            ->willReturn($totalBonus);

        return $moneyWrapper;
    }
}
