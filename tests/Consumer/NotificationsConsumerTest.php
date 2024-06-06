<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Consumers\NotificationsConsumer;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NotificationsConsumerTest extends TestCase
{
    private const ADMIN_EMAILS = [
        'gateway@mintme.com',
    ];

    private const MESSAGE_TYPE = 'not-enough-main-balance';
    private const CRYPTO_SYMBOL = 'WEB';
    private const CRYPTO_TOKEN_SYMBOL = 'USDC';
    private const TOKEN_NAME = 'TOKEN';
    private const USER_ID = 1;
    private const TYPE = 'withdrawal';

    public function testExecuteDBConnectionFailed(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(),
            $this->mockLogger(),
            $this->mockEntityManager(false),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertFalse($dc->execute($this->mockMessage('')));
    }

    public function testExecuteNotSupportedEmailType(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(),
            $this->mockLogger(1, 'Not supported notification type'),
            $this->mockEntityManager(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertFalse(
            $dc->execute($this->mockMessage((string)json_encode([
                'type' => 'NOT_SUPPORTED_TYPE',
                'message' => [
                    'userId' => self::USER_ID,
                    'amount' => '1',
                    'type' => self::TYPE,
                    'crypto' => self::CRYPTO_SYMBOL,
                    'cryptoBalance' => '0',
                    'cryptoNeed' => '1',
                ],
            ])))
        );
    }

    public function testExecuteUserNotFound(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(),
            $this->mockLogger(1, 'Received new message with undefined user'),
            $this->mockEntityManager(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'type' => self::MESSAGE_TYPE,
                'message' => [
                    'userId' => -1,
                    'amount' => '1',
                    'type' => self::TYPE,
                    'crypto' => self::CRYPTO_SYMBOL,
                    'cryptoBalance' => '0',
                    'cryptoNeed' => '1',
                ],
            ])))
        );
    }

    public function testExecuteCryptoNotFound(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(),
            $this->mockLogger(1, 'Invalid crypto: INVALID_CRYPTO given'),
            $this->mockEntityManager(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'type' => self::MESSAGE_TYPE,
                'message' => [
                    'userId' => self::USER_ID,
                    'amount' => '1',
                    'type' => self::TYPE,
                    'crypto' => 'INVALID_CRYPTO',
                    'cryptoBalance' => '0',
                    'cryptoNeed' => '1',
                ],
            ])))
        );
    }

    public function testExecuteTradableNotFound(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(),
            $this->mockLogger(1, 'Asset \'INVALID_TOK\' was not found'),
            $this->mockEntityManager(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'type' => self::MESSAGE_TYPE,
                'message' => [
                    'userId' => self::USER_ID,
                    'amount' => '1',
                    'type' => self::TYPE,
                    'crypto' => self::CRYPTO_SYMBOL,
                    'cryptoBalance' => '0',
                    'cryptoNeed' => '1',
                    'token' => 'INVALID_TOK',
                    'tokenBalance' => '0',
                    'tokenNeed' => '1',
                ],
            ])))
        );
    }

    public function testExecuteTradableCantBeWithdrawnToCrypto(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(),
            $this->mockLogger(1, 'Crypto: USDC does not work in WEB network'),
            $this->mockEntityManager(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'type' => self::MESSAGE_TYPE,
                'message' => [
                    'userId' => self::USER_ID,
                    'amount' => '1',
                    'type' => self::TYPE,
                    'crypto' => self::CRYPTO_SYMBOL,
                    'cryptoBalance' => '0',
                    'cryptoNeed' => '1',
                    'token' => self::CRYPTO_TOKEN_SYMBOL,
                    'tokenBalance' => '0',
                    'tokenNeed' => '1',
                ],
            ])))
        );
    }

    public function testExecuteBalanceCryptoSuccess(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(1),
            $this->mockLogger(2),
            $this->mockEntityManager(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(1),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'type' => self::MESSAGE_TYPE,
                'message' => [
                    'userId' => self::USER_ID,
                    'amount' => '1',
                    'type' => self::TYPE,
                    'crypto' => self::CRYPTO_SYMBOL,
                    'cryptoBalance' => '0',
                    'cryptoNeed' => '1',
                ],
            ])))
        );
    }

    public function testExecuteBalanceTokenSuccess(): void
    {
        $dc = new NotificationsConsumer(
            $this->mockMailer(1),
            $this->mockLogger(2),
            $this->mockEntityManager(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockEventDispatcher(1),
            $this->mockMoneyWrapper(),
            $this->mockWrappedCryptoTokenManager(),
            self::ADMIN_EMAILS
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'type' => self::MESSAGE_TYPE,
                'message' => [
                    'userId' => self::USER_ID,
                    'amount' => '1',
                    'type' => self::TYPE,
                    'crypto' => self::CRYPTO_SYMBOL,
                    'cryptoBalance' => '0',
                    'cryptoNeed' => '1',
                    'token' => self::TOKEN_NAME,
                    'tokenBalance' => '0',
                    'tokenNeed' => '1',
                ],
            ])))
        );
    }

    private function mockMailer(int $adminMailCount = 0): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->exactly($adminMailCount))->method('sendLackBalanceReportMail');

        return $mailer;
    }

    private function mockLogger(int $infoCount = 0, ?string $warningMessage = null): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly($infoCount))
            ->method('info');

        $logger->expects($warningMessage ? $this->once() : $this->never())
            ->method('warning')
            ->willReturnCallback(function ($message) use ($warningMessage): void {
                $this->assertStringContainsString($warningMessage, $message);
            });

        return $logger;
    }

    private function mockEntityManager(
        bool $isConnected = true
    ): EntityManagerInterface {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(($isConnected ? 0 : 2) + 1))
            ->method('getConnection')
            ->willReturn($this->mockConnection($isConnected));

        return $em;
    }

    private function mockConnection(bool $isConnected = true): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('ping')
            ->willReturn($isConnected);

        $connection->expects($isConnected ? $this->never() : $this->once())
            ->method('close');

        $connection->expects($isConnected ? $this->never() : $this->once())
            ->method('connect')
            ->willThrowException(new \Exception());

        return $connection;
    }

    private function mockUserManager(): UserManagerInterface
    {
        $um = $this->createMock(UserManagerInterface::class);
        $um->method('find')->willReturnCallback(function ($id): ?User {
            return self::USER_ID === $id
                ? $this->createMock(User::class)
                : null;
        });

        return $um;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->method('findBySymbol')->willReturnCallback(function ($symbol): ?Crypto {
            if (self::CRYPTO_SYMBOL === $symbol) {
                return $this->mockCrypto();
            }

            if (self::CRYPTO_TOKEN_SYMBOL === $symbol) {
                return $this->mockCryptoToken();
            }

            return null;
        });

        return $cm;
    }

    private function mockEventDispatcher(int $count = 0): EventDispatcherInterface
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->exactly($count))
            ->method('dispatch');

        return $eventDispatcher;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->method('parse')->willReturnCallback(function ($value, $symbol) {
            return new Money($value, new Currency($symbol));
        });
        $moneyWrapper->method('format')->willReturnCallback(function ($money) {
            return $money->getAmount();
        });

        return $moneyWrapper;
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn(self::CRYPTO_SYMBOL);
        $crypto->method('getMoneySymbol')->willReturn(self::CRYPTO_SYMBOL);

        return $crypto;
    }

    private function mockCryptoToken(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn(self::CRYPTO_TOKEN_SYMBOL);
        $crypto->method('getMoneySymbol')->willReturn(self::CRYPTO_TOKEN_SYMBOL);
        $crypto->method('canBeWithdrawnTo')->willReturn(false);

        return $crypto;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->method('findByName')->willReturnCallback(function ($tokenName): ?Token {
            return self::TOKEN_NAME === $tokenName
                ? $this->mockToken()
                : null;
        });

        return $tm;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getMoneySymbol')->willReturn(Symbols::TOK);

        return $token;
    }

    private function mockMessage(string $message): AMQPMessage
    {
        $msg = $this->createMock(AMQPMessage::class);
        $msg->body = $message;

        return $msg;
    }

    private function mockWrappedCryptoTokenManager(): WrappedCryptoTokenManagerInterface
    {
        return $this->createMock(WrappedCryptoTokenManagerInterface::class);
    }
}
