<?php declare(strict_types = 1);

namespace App\Tests\Wallet;

use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\CryptoManagerInterface;
use App\Manager\PendingManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Wallet;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
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

        $tokenTransactions = [
            $this->mockTransaction(9876546, 'foo', 'withdraw'),
            $this->mockTransaction(9876542, 'bar', 'deposit'),
        ];

        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface($withdrawTransactions),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator($depositTransactions),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager(),
            $this->mockContractHandler($tokenTransactions),
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
                    9876546,
                    'foo',
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
                    9876542,
                    'bar',
                    'deposit',
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
                [
                    $history[4]->getDate()->getTimestamp(),
                    $history[4]->getCrypto()->getSymbol(),
                    $history[4]->getType()->getTypeCode(),
                ],
                [
                    $history[5]->getDate()->getTimestamp(),
                    $history[5]->getCrypto()->getSymbol(),
                    $history[5]->getType()->getTypeCode(),
                ],
            ]
        );
    }

    public function testWithdrawInitCrypto(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000000000'),
            $this->mockBalanceHandler($this->once(), '3000000000000000000'),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->once()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager(),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0x123'),
            $this->mockAmount(new Money('1000000000000000000', new Currency(Token::WEB_SYMBOL))),
            $this->mockCrypto(Token::WEB_SYMBOL)
        );
    }

    public function testWithdrawInitCryptoWithLowBalance(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '1000000000000000000'),
            $this->mockBalanceHandler($this->never(), '1000000000000000000'),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager(),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $this->expectException(NotEnoughUserAmountException::class);

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0x123'),
            $this->mockAmount(new Money('1000000000000000000', new Currency(Token::WEB_SYMBOL))),
            $this->mockCrypto(Token::WEB_SYMBOL)
        );
    }

    public function testWithdrawInitToken(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([]),
            $this->mockBalanceHandler(
                $this->exactly(2),
                '3000000000000000000',
                '3000000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->once()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager($this->once()),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0x123'),
            $this->mockAmount(new Money('1000000000000', new Currency(MoneyWrapper::TOK_SYMBOL))),
            $this->mockToken()
        );
    }

    public function testWithdrawInitTokenWithNullCrypto(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([]),
            $this->mockBalanceHandler(
                $this->never(),
                '3000000000000000000',
                '3000000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager($this->once(), true),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $this->expectException(NotFoundTokenException::class);

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0x123'),
            $this->mockAmount(new Money('1000000000000', new Currency(MoneyWrapper::TOK_SYMBOL))),
            $this->mockToken()
        );
    }

    public function testWithdrawInitTokenWithLowBalance(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([]),
            $this->mockBalanceHandler(
                $this->never(),
                '3000000000000000000',
                '100000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager($this->once()),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $this->expectException(NotEnoughUserAmountException::class);

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0x123'),
            $this->mockAmount(new Money('1000000000000', new Currency(MoneyWrapper::TOK_SYMBOL))),
            $this->mockToken()
        );
    }

    public function testWithdrawInitTokenWithNoEnoughFee(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([]),
            $this->mockBalanceHandler(
                null,
                '1000000000000000',
                '3000000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager($this->once()),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $this->expectException(NotEnoughAmountException::class);

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0x123'),
            $this->mockAmount(new Money('1000000000000', new Currency(MoneyWrapper::TOK_SYMBOL))),
            $this->mockToken()
        );
    }

    public function testWithdrawCommitCrypto(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000000000', $this->once()),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager(),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager(),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $wallet->withdrawCommit($this->mockPendingWithdraw('1000000000000000000', Token::WEB_SYMBOL, false));
    }

    public function testWithdrawCommitCryptoWithLowBalance(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '10000000000000000', $this->never()),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager(),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager(),
            $this->mockContractHandler([]),
            $this->createMock(LoggerInterface::class)
        );

        $this->expectException(NotEnoughAmountException::class);

        $wallet->withdrawCommit($this->mockPendingWithdraw('1000000000000000000', Token::WEB_SYMBOL, false));
    }

    public function testWithdrawCommitToken(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([]),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager(),
            $this->createMock(EntityManagerInterface::class),
            $this->mockCryptoManager(),
            $this->mockContractHandler([], $this->once()),
            $this->createMock(LoggerInterface::class)
        );

        $wallet->withdrawCommit($this->mockPendingWithdraw('1000000000000', MoneyWrapper::TOK_SYMBOL, true));
    }

    private function mockPendingWithdraw(string $amount, string $symbol, bool $isToken = false): PendingWithdrawInterface
    {
        if ($isToken) {
            $pending = $this->createMock(PendingTokenWithdraw::class);
            $pending->method('getToken')->willReturn($this->mockToken());
        } else {
            $pending = $this->createMock(PendingWithdraw::class);
            $pending->method('getCrypto')->willReturn($this->mockCrypto(Token::WEB_SYMBOL));
        }

        $amountMock = $this->createMock(Amount::class);
        $amountMock->method('getAmount')->willReturn(
            new Money($amount, new Currency($symbol))
        );

        $pending->method('getAmount')->willReturn($amountMock);

        return $pending;
    }

    private function mockPendingManager(?Invocation $inv = null): PendingManagerInterface
    {
        $manager = $this->createMock(PendingManagerInterface::class);
        $manager->expects($inv ?? $this->never())->method('create');

        return $manager;
    }

    private function mockAmount(Money $money): Amount
    {
        $amount = $this->createMock(Amount::class);
        $amount->method('getAmount')->willReturn($money);

        return $amount;
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

    private function mockContractHandler(array $transactions, ?Invocation $withdrawInv = null): ContractHandlerInterface
    {
        $contractHandler = $this->createMock(ContractHandlerInterface::class);
        $contractHandler->expects($withdrawInv ?? $this->never())->method('withdraw');
        $contractHandler
            ->method('getTransactions')
            ->willReturn($transactions);

        return $contractHandler;
    }

    private function mockWithdrawGatewayInterface(
        array $history,
        string $available = '0',
        ?Invocation $withdrawInv = null
    ): WithdrawGatewayInterface {
        $withdrawGatewayMock = $this->createMock(WithdrawGatewayInterface::class);
        $withdrawGatewayMock
            ->method('getHistory')
            ->willReturn($history)
        ;
        $withdrawGatewayMock
            ->method('getBalance')
            ->willReturn(new Money($available, new Currency(Token::WEB_SYMBOL)));

        $withdrawGatewayMock
            ->expects($withdrawInv ?? $this->never())
            ->method('withdraw');

        return $withdrawGatewayMock;
    }

    private function mockBalanceHandler(
        ?Invocation $withdrawInv = null,
        string $available = '0',
        string $availableToken = '0'
    ): BalanceHandlerInterface {
        $balanceResultCrypto = $this->createMock(BalanceResult::class);
        $balanceResultCrypto->method('getAvailable')
            ->willReturn(new Money($available, new Currency(Token::WEB_SYMBOL)));

        $balanceResultToken = $this->createMock(BalanceResult::class);
        $balanceResultToken->method('getAvailable')
            ->willReturn(new Money($availableToken, new Currency(MoneyWrapper::TOK_SYMBOL)));

        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->method('balance')
            ->will($this->returnCallback(
                function (User $user, TradebleInterface $tradable) use ($balanceResultCrypto, $balanceResultToken) {
                    return Token::WEB_SYMBOL === $tradable->getSymbol()
                        ? $balanceResultCrypto
                        : $balanceResultToken;
                }
            ));

        $handler->expects($withdrawInv ?? $this->never())->method('withdraw');

        return $handler;
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

    private function mockCrypto(string $symbol): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);
        $cryptoMock
            ->method('getFee')
            ->willReturn(
                new Money('3000000000000000', new Currency($symbol))
            );
        $cryptoMock
            ->method('getSymbol')
            ->willReturn($symbol)
        ;

        return $cryptoMock;
    }

    private function mockCryptoManager(?Invocation $inv = null, bool $nullCrypto = false): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);

        if ($nullCrypto) {
            $manager->expects($inv ?? $this->never())
                ->method('findBySymbol')
                ->willReturn(null);
        } else {
            $manager->expects($inv ?? $this->never())
                ->method('findBySymbol')
                ->with(Token::WEB_SYMBOL)
                ->willReturn($this->mockCrypto(Token::WEB_SYMBOL));
        }

        return $manager;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
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
