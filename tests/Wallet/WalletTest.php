<?php declare(strict_types = 1);

namespace App\Tests\Wallet;

use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\Config;
use App\Manager\PendingManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Model\Address;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Wallet;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WalletTest extends TestCase
{
    public function testGetWithdrawDepositHistory(): void
    {
        $depositTransactions = [
            $this->mockTransaction(9876541, 'WEB', 'deposit'),
            $this->mockTransaction(9876545, 'BTC', 'deposit'),
        ];
        $withdrawTransactions = [
            $this->mockTransaction(9876543, 'ETH', 'withdraw'),
            $this->mockTransaction(9876547, 'XMR', 'withdraw'),
        ];

        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface($withdrawTransactions),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator($depositTransactions),
            $this->createMock(ContractHandlerInterface::class),
            $this->createMock(PendingManagerInterface::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Config::class),
            $this->createMock(LoggerInterface::class)
        );

        $history = $wallet->getWithdrawDepositHistory($this->mockUser(), 0, 10);

        $this->assertEquals(
            [
                [
                    9876547,
                    'XMR',
                    'withdraw',
                ],
                [
                    9876545,
                    'BTC',
                    'deposit',
                ],
                [
                    9876543,
                    'ETH',
                    'withdraw',
                ],
                [
                    9876541,
                    'WEB',
                    'deposit',
                ],
            ],
            [
                [
                    $history[0]->getDate()->getTimestamp(),
                    $history[0]->getCrypto()->getSymbol(),
                    $history[0]->getType()->getTypeCode(),
                ],
                [
                    $history[1]->getDate()->getTimestamp(),
                    $history[1]->getCrypto()->getSymbol(),
                    $history[1]->getType()->getTypeCode(),
                ],
                [
                    $history[2]->getDate()->getTimestamp(),
                    $history[2]->getCrypto()->getSymbol(),
                    $history[2]->getType()->getTypeCode(),
                ],
                [
                    $history[3]->getDate()->getTimestamp(),
                    $history[3]->getCrypto()->getSymbol(),
                    $history[3]->getType()->getTypeCode(),
                ],
            ]
        );
    }

    private function mockDepositCommunicator(array $transactions): DepositGatewayCommunicator
    {
        $depositCommunicatorMock = $this->createMock(DepositGatewayCommunicator::class);
        $depositCommunicatorMock
            ->method('getTransactions')
            ->willReturn($transactions)
        ;

        return $depositCommunicatorMock;
    }

    private function mockWithdrawGatewayInterface(array $history): WithdrawGatewayInterface
    {
        $withdrawGatewayMock = $this->createMock(WithdrawGatewayInterface::class);
        $withdrawGatewayMock
            ->method('getHistory')
            ->willReturn($history)
        ;

        return $withdrawGatewayMock;
    }

    private function mockBalanceHandler(): BalanceHandlerInterface
    {
        return $this->createMock(BalanceHandlerInterface::class);
    }

    private function mockTransaction(int $timestamp, string $crypto, string $type): Transaction
    {
        $transactionMock = $this->createMock(Transaction::class);
        $transactionMock
            ->method('getDate')
            ->willReturn($this->mockDateTime($timestamp))
        ;
        $transactionMock
            ->method('getCrypto')
            ->willReturn($this->mockCrypto($crypto))
        ;
        $transactionMock
            ->method('getType')
            ->willReturn($this->mockType($type))
        ;

        return $transactionMock;
    }

    private function mockType(string $type): Type
    {
        $typeMock = $this->createMock(Type::class);
        $typeMock
            ->method('getTypeCode')
            ->willReturn($type)
        ;

        return $typeMock;
    }

    private function mockCrypto(string $crypto): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);
        $cryptoMock
            ->method('getSymbol')
            ->willReturn($crypto)
        ;

        return $cryptoMock;
    }

    private function mockDateTime(int $timestamp): DateTime
    {
        $dateMock = $this->createMock(DateTime::class);
        $dateMock
            ->method('getTimestamp')
            ->willReturn($timestamp)
        ;

        return $dateMock;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    public function mockAddress(string $str): Address
    {
        $address = $this->createMock(Address::class);

        $address->method('getAddress')->willReturn($str);

        return $address;
    }
}
