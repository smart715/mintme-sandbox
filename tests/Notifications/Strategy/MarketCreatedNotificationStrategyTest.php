<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Crypto;
use App\Entity\Image;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\MarketCreatedNotificationStrategy;
use PHPUnit\Framework\TestCase;

class MarketCreatedNotificationStrategyTest extends TestCase
{
    private const CRYPTO_NAME = 'TEST_CRYPTO';
    private const TOKEN_NAME = 'TEST_TOKEN';
    private const CRYPTO_AVATAR = '/media/default_mintme.png';
    private const TOKEN_AVATAR = '/media/default_token.png';
    public function testSendNotification(): void
    {
        $type = 'TEST';
        $user = $this->createMock(User::class);
        $notificationStrategy = new MarketCreatedNotificationStrategy(
            $this->mockTokenCrypto(),
            $type,
            $this->mockUserNotificationManager([$user, $type]),
            $this->mockMailer()
        );

        $notificationStrategy->sendNotification($user);
    }

    private function mockUserNotificationManager(array $data): UserNotificationManagerInterface
    {
        [$user, $type] = $data;

        $notificationManager = $this->createMock(UserNotificationManagerInterface::class);

        $notificationManager->expects($this->once())
            ->method('createNotification')
            ->with(
                $user,
                $type,
                (array)json_encode([
                    'tokenName' => self::TOKEN_NAME,
                    'cryptoSymbol' => self::CRYPTO_NAME,
                    'tokenAvatar' =>self::TOKEN_AVATAR,
                    'cryptoAvatar' =>self::CRYPTO_AVATAR,
                ])
            );

        return $notificationManager;
    }

    private function mockMailer(): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('sendMarketCreatedMail');

        return $mailer;
    }

    private function mockTokenCrypto(): TokenCrypto
    {
        $tokenCrypto = $this->createMock(TokenCrypto::class);
        $tokenCrypto->method('getToken')
            ->willReturn($this->mockToken());

        $tokenCrypto->method('getCrypto')
            ->willReturn($this->mockCrypto());

        return $tokenCrypto;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getName')
            ->willReturn(self::TOKEN_NAME);
        $token->method('getImage')
            ->willReturn($this->mockTokenImage());

        return $token;
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')
            ->willReturn(self::CRYPTO_NAME);
        $crypto->method('getImage')
            ->willReturn($this->mockCryptoImage());

        return $crypto;
    }

    private function mockTokenImage(): Image
    {
        $image = $this->createMock(Image::class);
        $image->method('getUrl')
            ->willReturn(self::TOKEN_AVATAR);

        return $image;
    }
    private function mockCryptoImage(): Image
    {
        $image = $this->createMock(Image::class);
        $image->method('getUrl')
            ->willReturn(self::CRYPTO_AVATAR);

        return $image;
    }
}
