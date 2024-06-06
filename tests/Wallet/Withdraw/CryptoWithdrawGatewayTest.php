<?php declare(strict_types = 1);

namespace App\Tests\Wallet\Withdraw;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Utils\Symbols;
use App\Wallet\Model\Transaction;
use App\Wallet\Withdraw\Communicator\CommunicatorInterface;
use App\Wallet\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use App\Wallet\Withdraw\CryptoWithdrawGateway;
use App\Wallet\Withdraw\Fetcher\Mapper\MapperInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CryptoWithdrawGatewayTest extends TestCase
{
    public function testWithdraw(): void
    {
        $user = $this->createMock(User::class);
        $balance = new Money('1', new Currency(Symbols::WEB));
        $address = '0x213456789';
        $crypto = $this->createMock(Crypto::class);
        $fee = null;

        $communicator = $this->mockCommunicator();
        $communicator
            ->expects($this->once())
            ->method('sendWithdrawRequest')
            ->with($user, $balance, $address, $crypto, $fee);

        $mapper = $this->mockMapper();

        $cryptoWithdrawGateway = new CryptoWithdrawGateway($communicator, $mapper);

        $cryptoWithdrawGateway->withdraw($user, $balance, $address, $crypto, $fee);
    }

    public function testRetryWithdraw(): void
    {
        $withdrawCallbackMsg = $this->createMock(WithdrawCallbackMessage::class);

        $communicator = $this->mockCommunicator();
        $communicator
            ->expects($this->once())
            ->method('sendRetryMessage')
            ->with($withdrawCallbackMsg);

        $mapper = $this->mockMapper();

        $cryptoWithdrawGateway = new CryptoWithdrawGateway($communicator, $mapper);

        $cryptoWithdrawGateway->retryWithdraw($withdrawCallbackMsg);
    }

    public function testGetHistory(): void
    {
        $user = $this->createMock(User::class);
        $offset = 0;
        $limit = 50;

        $result = [
            $this->createMock(Transaction::class),
            $this->createMock(Transaction::class),
            $this->createMock(Transaction::class),
        ];

        $communicator = $this->mockCommunicator();
        $mapper = $this->mockMapper();
        $mapper
            ->expects($this->once())
            ->method('getHistory')
            ->with($user, $offset, $limit)
            ->willReturn($result);

        $cryptoWithdrawGateway = new CryptoWithdrawGateway($communicator, $mapper);

        $this->assertEquals($result, $cryptoWithdrawGateway->getHistory($user, $offset, $limit));
    }

    public function testGetBalance(): void
    {
        $tradable = $this->createMock(TradableInterface::class);
        $cryptoNetwork = $this->createMock(Crypto::class);
        $expectedBalance = new Money('1', new Currency(Symbols::WEB));

        $communicator = $this->mockCommunicator();
        $mapper = $this->mockMapper();
        $mapper
            ->expects($this->once())
            ->method('getBalance')
            ->with($tradable, $cryptoNetwork)
            ->willReturn($expectedBalance);

        $cryptoWithdrawGateway = new CryptoWithdrawGateway($communicator, $mapper);

        $this->assertEquals($expectedBalance, $cryptoWithdrawGateway->getBalance($tradable, $cryptoNetwork));
    }

    public function testIsContractAddress(): void
    {
        $contractAddress = '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48';
        $crypto = 'WEB';

        $communicator = $this->mockCommunicator();
        $mapper = $this->mockMapper();
        $mapper
            ->expects($this->exactly(2))
            ->method('isContractAddress')
            ->willReturnCallback(fn($addr, $crypt) => $contractAddress === $addr && $crypto === $crypt);

        $cryptoWithdrawGateway = new CryptoWithdrawGateway($communicator, $mapper);

        $this->assertTrue($cryptoWithdrawGateway->isContractAddress($contractAddress, $crypto));
        $this->assertFalse($cryptoWithdrawGateway->isContractAddress('unknown', $crypto));
    }

    public function testGetUserInt(): void
    {
        $address = '0xRand';
        $cryptoNetwork = 'WEB';
        $userId = 10;

        $communicator = $this->mockCommunicator();
        $mapper = $this->mockMapper();
        $mapper
            ->expects($this->exactly(2))
            ->method('getUserId')
            ->willReturnCallback(fn($addr, $cr) => $address === $addr && $cryptoNetwork === $cr ? $userId : null);

        $cryptoWithdrawGateway = new CryptoWithdrawGateway($communicator, $mapper);

        $this->assertSame($userId, $cryptoWithdrawGateway->getUserId($address, $cryptoNetwork));
        $this->assertSame(null, $cryptoWithdrawGateway->getUserId('0xunknown', $cryptoNetwork));
    }

    /**
     * @return MockObject|CommunicatorInterface
     */
    private function mockCommunicator(): CommunicatorInterface
    {
        return $this->createMock(CommunicatorInterface::class);
    }

    /**
     * @return MockObject|MapperInterface
     */
    private function mockMapper(): MapperInterface
    {
        return $this->createMock(MapperInterface::class);
    }
}
