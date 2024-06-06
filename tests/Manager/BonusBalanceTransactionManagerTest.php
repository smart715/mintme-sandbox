<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\BonusBalanceTransaction;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Manager\BonusBalanceTransactionManager;
use App\Manager\UserTokenManagerInterface;
use App\Repository\BonusBalanceTransactionRepository;
use App\Repository\CryptoRepository;
use App\Repository\TokenRepository;
use App\Utils\Symbols;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BonusBalanceTransactionManagerTest extends TestCase
{
    public function testGetBalances(): void
    {
        $user = $this->createMock(User::class);
        $tokenId = '1';
        $cryptoId = null;

        /** @var MockObject|Token $tradable */
        $tradable = $this->createMock(Token::class);
        $withdraw = '200000000000000';
        $deposit = '300000000000000';

        $tradable
            ->method('getId')
            ->willReturn((int)$tokenId);

        $tradable
            ->method('getMoneySymbol')
            ->willReturn('TOK');

        $bbTransaction = [
            'token_id' => $tokenId,
            'crypto_id' => $cryptoId,
            'deposit' => $deposit,
            'withdraw' => $withdraw,
        ];

        $balances = [$bbTransaction];

        /** @var MockObject|TokenRepository $tokenRepository */
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository->method('findBy')->willReturn([$tradable]);

        $cryptoRepository = $this->createMock(CryptoRepository::class);
        $cryptoRepository->method('findBy')->willReturn([]);

        /** @var MockObject|BonusBalanceTransactionRepository $bbtRepository */
        $bbtRepository = $this->createMock(BonusBalanceTransactionRepository::class);
        $bbtRepository->method('getBalancesByUser')->with($user)->willReturn($balances);

        $em = $this->createMock(EntityManagerInterface::class);

        $logger = $this->createMock(LoggerInterface::class);

        $returnedBalances = [
            (new BonusBalanceTransaction($tradable))
                ->setAmount(
                    (new Money($deposit, new Currency(Symbols::TOK)))
                        ->subtract(new Money($withdraw, new Currency(Symbols::TOK)))
                ),
        ];

        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $bbtManager = new BonusBalanceTransactionManager(
            $em,
            $userTokenManager,
            $bbtRepository,
            $tokenRepository,
            $cryptoRepository,
            $logger
        );

        $this->assertEquals(
            $returnedBalances[0]->getTradable(),
            $bbtManager->getBalances($user)[0]->getTradable()
        );
    }

    public function testGetBalancesEmpty(): void
    {
        $user = $this->createMock(User::class);

        /** @var MockObject|BonusBalanceTransactionRepository $bbtRepository */
        $bbtRepository = $this->createMock(BonusBalanceTransactionRepository::class);

        $returnedBalances = [];

        $em = $this->createMock(EntityManagerInterface::class);

        $tokenRepository = $this->createMock(TokenRepository::class);
        $cryptoRepository = $this->createMock(CryptoRepository::class);

        $logger = $this->createMock(LoggerInterface::class);

        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $bbtManager = new BonusBalanceTransactionManager(
            $em,
            $userTokenManager,
            $bbtRepository,
            $tokenRepository,
            $cryptoRepository,
            $logger
        );

        $this->assertEquals($returnedBalances, $bbtManager->getBalances($user));
    }

    public function testUpdateBalance(): void
    {
        $user = $this->createMock(User::class);
        $token = $this->createMock(Token::class);
        $amount = new Money('200000000000000', new Currency(Symbols::TOK));
        $type = Type::DEPOSIT;
        $bonusType = 'bonus_type';

        /** @var MockObject|BonusBalanceTransactionRepository $bbtRepository */
        $bbtRepository = $this->createMock(BonusBalanceTransactionRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $tokenRepository = $this->createMock(TokenRepository::class);
        $cryptoRepository = $this->createMock(CryptoRepository::class);

        $logger = $this->createMock(LoggerInterface::class);

        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $userTokenManager->expects($this->once())->method('updateRelation')->with($user, $token, $amount);

        $bbtManager = new BonusBalanceTransactionManager(
            $em,
            $userTokenManager,
            $bbtRepository,
            $tokenRepository,
            $cryptoRepository,
            $logger
        );

        $bbtManager->updateBalance($user, $token, $amount, $type, $bonusType);
    }

    public function testGetBalanceExisted(): void
    {
        $user = $this->createMock(User::class);

        /** @var MockObject|TradableInterface $token */
        $token = $this->createMock(Token::class);
        $tokenId = '1';
        $withdraw = '200000000000000';
        $deposit = '300000000000000';

        $token
            ->method('getMoneySymbol')
            ->willReturn('TOK');

        $bbTransaction = [
            'token_id' => $tokenId,
            'deposit' => $deposit,
            'withdraw' => $withdraw,
        ];

        $balances = $bbTransaction;

        /** @var MockObject|BonusBalanceTransactionRepository $bbtRepository */
        $bbtRepository = $this->createMock(BonusBalanceTransactionRepository::class);
        $bbtRepository->method('getBalance')->with($user, $token)->willReturn($balances);

        $returnedBalance = (new Money($deposit, new Currency(Symbols::TOK)))->subtract(
            new Money($withdraw, new Currency(Symbols::TOK))
        );

        $em = $this->createMock(EntityManagerInterface::class);

        $tokenRepository = $this->createMock(TokenRepository::class);
        $cryptoRepository = $this->createMock(CryptoRepository::class);

        $logger = $this->createMock(LoggerInterface::class);

        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $bbtManager = new BonusBalanceTransactionManager(
            $em,
            $userTokenManager,
            $bbtRepository,
            $tokenRepository,
            $cryptoRepository,
            $logger
        );

        $this->assertEquals($returnedBalance, $bbtManager->getBalance($user, $token));
    }

    public function testGetBalanceNonExisted(): void
    {
        $user = $this->createMock(User::class);
        /** @var TradableInterface $token */
        $token = $this->createMock(Token::class);

        /** @var MockObject|BonusBalanceTransactionRepository $bbtRepository */
        $bbtRepository = $this->createMock(BonusBalanceTransactionRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $tokenRepository = $this->createMock(TokenRepository::class);
        $cryptoRepository = $this->createMock(CryptoRepository::class);

        $logger = $this->createMock(LoggerInterface::class);

        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $bbtManager = new BonusBalanceTransactionManager(
            $em,
            $userTokenManager,
            $bbtRepository,
            $tokenRepository,
            $cryptoRepository,
            $logger
        );

        $this->assertEquals(null, $bbtManager->getBalance($user, $token));
    }

    public function getTransactionsEmpty(): void
    {
        $user = $this->createMock(User::class);

        /** @var MockObject|BonusBalanceTransactionRepository $bbtRepository */
        $bbtRepository = $this->createMock(BonusBalanceTransactionRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $tokenRepository = $this->createMock(TokenRepository::class);
        $cryptoRepository = $this->createMock(CryptoRepository::class);

        $logger = $this->createMock(LoggerInterface::class);

        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $bbtManager = new BonusBalanceTransactionManager(
            $em,
            $userTokenManager,
            $bbtRepository,
            $tokenRepository,
            $cryptoRepository,
            $logger
        );

        $this->assertEquals([], $bbtManager->getTransactions($user, 0, 10));
    }

    public function getTransactions(): void
    {
        $user = $this->createMock(User::class);
        $token = $this->createMock(Token::class);
        $amount = new Money('200000000000000', new Currency(Symbols::TOK));

        $bbTransaction = (new BonusBalanceTransaction($token))
            ->setUser($user)
            ->setAmount($amount)
            ->setType(Type::DEPOSIT)
            ->setBonusType('bonus_type');

        $transactions = [new Transaction(
            DateTime::createFromImmutable($bbTransaction->getCreatedAt()),
            '',
            '',
            '',
            $bbTransaction->getAmount(),
            null,
            $bbTransaction->getToken(),
            Status::fromString(Status::PAID),
            Type::fromString($bbTransaction->getType()),
            true
        )];

        /** @var MockObject|BonusBalanceTransactionRepository $bbtRepository */
        $bbtRepository = $this->createMock(BonusBalanceTransactionRepository::class);
        $bbtRepository->method('getTransactions')->willReturn([$bbTransaction]);

        $tokenRepository = $this->createMock(TokenRepository::class);
        $cryptoRepository = $this->createMock(CryptoRepository::class);

        $logger = $this->createMock(LoggerInterface::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $bbtManager = new BonusBalanceTransactionManager(
            $em,
            $userTokenManager,
            $bbtRepository,
            $tokenRepository,
            $cryptoRepository,
            $logger
        );

        $this->assertEquals($transactions, $bbtManager->getTransactions($user, 0, 10));
    }
}
