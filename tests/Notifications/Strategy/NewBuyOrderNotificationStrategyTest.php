<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Market;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NewBuyOrderNotificationStrategy;
use PHPUnit\Framework\TestCase;

class NewBuyOrderNotificationStrategyTest extends TestCase
{
    private const CRYPTO_NAME = 'TEST_CRYPTO';
    private const TOKEN_NAME = 'TEST_TOKEN';
    private const INVESTOR_NICKNAME = 'TEST_INVESTOR_NICKNAME';

    public function testSendNotification(): void
    {
        $user = $this->mockUser();
        $investor = $this->mockUser();
        $type = 'new_buy_order';
        $jsonData = (array)json_encode(
            [
                'nickname' => self::INVESTOR_NICKNAME,
                'tokenName' => self::TOKEN_NAME,
                'crypto' => self::CRYPTO_NAME,
            ]
        );

        $notificationStrategy = new NewBuyOrderNotificationStrategy(
            $this->mockProfile($investor),
            $this->mockMarket(),
            $type,
            $this->mockUserNotificationManager([$user, $type, $jsonData]),
            $this->mockMailer($user, $investor)
        );

        $notificationStrategy->sendNotification($user);
    }


    private function mockMailer(User $user, User $investor): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);

        $mailer->expects($this->once())
            ->method('sendNewBuyOrderMail')
            ->with($user, $investor, self::TOKEN_NAME, self::CRYPTO_NAME);

        return $mailer;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockUserNotificationManager(array $data): UserNotificationManagerInterface
    {
        [$user, $type, $jsonData] = $data;

        $notificationManager = $this->createMock(UserNotificationManagerInterface::class);

        $notificationManager->expects($this->once())
            ->method('createNotification')
            ->with($user, $type, $jsonData);

        return $notificationManager;
    }

    private function mockProfile(User $investor): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->expects($this->once())
            ->method('getNickname')
            ->willReturn(self::INVESTOR_NICKNAME);

        $profile->expects($this->once())
            ->method('getUser')
            ->willReturn($investor);

        return $profile;
    }

    private function mockMarket(): Market
    {
        $market = $this->createMock(Market::class);
        $market->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->mockToken());

        $market->expects($this->once())
            ->method('getBase')
            ->willReturn($this->mockCrypto());

        return $market;
    }
    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getName')
            ->willReturn(self::TOKEN_NAME);

        return $token;
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')
            ->willReturn(self::CRYPTO_NAME);

        return $crypto;
    }
}
