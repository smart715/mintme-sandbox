<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\InternalTransaction\InternalTransaction;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Manager\InternalTransactionManager;
use App\Repository\InternalTransactionRepository;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Transaction;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InternalTransactionManagerTest extends TestCase
{
    /**
     * @dataProvider transferFundsDataProvider
     */
    public function testTransferFunds(
        string $tradable,
        ?string $exception,
        int $senderAmount,
        int $senderFee
    ): void {
        if ($exception) {
            /* @phpstan-ignore-next-line */
            $this->expectException($exception);
        }

        $repository = $this->mockRepository();

        $sender = $this->mockUser();
        $receiver = $this->mockUser();

        $tradable = 'token' === $tradable
            ? $this->mockToken()
            : ('crypto' === $tradable
                ? $this->mockCrypto()
                : $this->mockTradable());

        $tradable
            ->method('getSymbol')
            ->willReturn('TEST');

        $cryptoNetwork = $this->mockCrypto();

        $amount = $this->mockAmount();
        $amount
            ->method('getAmount')
            ->willReturn(new Money($senderAmount, new Currency('TEST')));

        $address = $this->mockAddress();

        $fee = (new Money($senderFee, new Currency('TEST')));

        $manager = new InternalTransactionManager($repository, $this->mockLimitHistoryConfig());
        $result = $manager->transferFunds(
            $sender,
            $receiver,
            $tradable,
            $cryptoNetwork,
            $amount,
            $address,
            $fee
        );

        $this->assertEquals(
            0,
            $result->getInternalWithdrawal()->getFee()->getAmount()
        );

        $this->assertEquals(
            $result->getInternalWithdrawal()->getAmount()->getAmount()->getAmount(),
            $senderAmount
        );

        $this->assertEquals(
            $result->getInternalDeposit()->getFee()->getAmount(),
            $senderFee
        );

        $this->assertEquals(
            $result->getInternalDeposit()->getAmount()->getAmount()->getAmount(),
            $senderAmount
        );
    }

    public function testGetLatest(): void
    {
        $user = $this->mockUser();
        $offset = 0;
        $limit = 10;
        $amount = 10;
        $address = 'TEST_ADDRESS';
        $fee = 1;

        $addressMock = $this->mockAddress();
        $addressMock->method('getAddress')->willReturn($address);

        $amountMock = $this->mockAmount();
        $amountMock->method('getAmount')->willReturn(new Money($amount, new Currency('TEST_CURRENCY')));

        $tradable = $this->mockToken();

        $transaction = $this->mockInternalTransaction();
        $transaction->method('getDate')->willReturn(new \DateTimeImmutable('2023-01-01'));
        $transaction->method('getAddress')->willReturn($addressMock);
        $transaction->method('getAmount')->willReturn($amountMock);
        $transaction->method('getFee')->willReturn(new Money($fee, new Currency('TEST_CURRENCY')));
        $transaction->method('getTradable')->willReturn($tradable);
        $transaction->method('getType')->willReturn('deposit');

        $repository = $this->mockRepository();
        $repository
            ->expects($this->once())
            ->method('getLatest')
            ->with($user, $offset, $limit)
            ->willReturn([$transaction]);

        $manager = new InternalTransactionManager($repository, $this->mockLimitHistoryConfig());

        /** @var Transaction[] $result */
        $result = $manager->getLatest($user, $offset, $limit);

        $this->assertEquals($result[0]->getAmount()->getAmount(), $amount);
        $this->assertEquals($result[0]->getAddress(), $address);
    }

    public function transferFundsDataProvider(): array
    {
        return [
            'Token deposit-withdraw' => [
                'tradable' => 'token',
                'exception' => null,
                'senderAmount' => 10,
                'senderFee' => 1,
            ],
            'Exception thrown due to token with incorrect amount' => [
                'tradable' => 'token',
                'exception' => \InvalidArgumentException::class,
                'senderAmount' => 0,
                'senderFee' => 1,
            ],
            'Crypto deposit-withdraw' => [
                'tradable' => 'crypto',
                'exception' => null,
                'senderAmount' => 10,
                'senderFee' => 1,
            ],
            'Exception thrown due to invalid senderAmount' => [
                'tradable' => 'crypto',
                'exception' => \InvalidArgumentException::class,
                'senderAmount' => 0,
                'senderFee' => 1,
            ],
            'Exception thrown due to invalid tradable' => [
                'tradable' => 'unknown',
                'exception' => \InvalidArgumentException::class,
                'senderAmount' => 10,
                'senderFee' => 1,
            ],
        ];
    }

    /** @return User|MockObject */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    /** @return Token|MockObject */
    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    /** @return Crypto|MockObject */
    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    /** @return Amount|MockObject */
    private function mockAmount(): Amount
    {
        return $this->createMock(Amount::class);
    }

    /** @return Address|MockObject */
    private function mockAddress(): Address
    {
        return $this->createMock(Address::class);
    }

    /** @return InternalTransactionRepository|MockObject */
    private function mockRepository(): InternalTransactionRepository
    {
        return $this->createMock(InternalTransactionRepository::class);
    }

    /** @return InternalTransaction|MockObject */
    private function mockInternalTransaction(): InternalTransaction
    {
        return $this->createMock(InternalTransaction::class);
    }

    /** @return TradableInterface|MockObject */
    private function mockTradable(): TradableInterface
    {
        return $this->createMock(TradableInterface::class);
    }

    private function mockLimitHistoryConfig(): LimitHistoryConfig
    {
        return $this->createMock(LimitHistoryConfig::class);
    }
}
