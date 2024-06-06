<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Image;
use App\Entity\Rewards\Reward;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currencies;
use Money\Money;
use PHPUnit\Framework\TestCase;

abstract class RewardNotificationStrategy extends TestCase
{
    protected const REWARD_TITLE = 'REWARD_TEST';
    protected const REWARD_SLUG = 'REWARD-TEST';
    protected const TOKEN_NAME = 'TOKEN_TEST';
    protected const TOKEN_AVATAR = '/media/default_token.png';
    protected const OWNER_NICKNAME = 'NICKNAME_TEST';
    protected const TYPE = 'TEST';

    protected User $user;
    protected Token $token;
    protected array $jsonData;

    public function setUp(): void
    {
        $this->user = $this->mockUser();
        $this->token = $this->mockToken();
        $this->jsonData = (array)json_encode([
            'rewardTitle' => self::REWARD_TITLE,
            'rewardToken' => self::TOKEN_NAME,
            'tokenAvatar' => self::TOKEN_AVATAR,
            'slug' => self::REWARD_SLUG,
        ]);
    }

    protected function mockMailer(User $user, string $method, array $args): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);

        $mailer->expects($this->once())
            ->method($method)
            ->with(...$args);

        return $mailer;
    }

    protected function mockUser(): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getNickname')
            ->willReturn(self::OWNER_NICKNAME);

        return $user;
    }

    protected function mockUserNotificationManager(array $data): UserNotificationManagerInterface
    {
        [$user, $type, $jsonData] = $data;

        $notificationManager = $this->createMock(UserNotificationManagerInterface::class);

        $notificationManager->expects($this->once())
            ->method('createNotification')
            ->with($user, $type, $jsonData);

        return $notificationManager;
    }

    protected function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getOwner')
            ->willReturn($this->mockUser());
        $token->method('getName')
            ->willReturn(self::TOKEN_NAME);
        $token->method('getImage')
            ->willReturn($this->mockTokenImage());

        return $token;
    }


    protected function mockReward(?Token $token = null): Reward
    {
        $reward = $this->createMock(Reward::class);
        $reward->expects($this->once())
            ->method('getTitle')
            ->willReturn(self::REWARD_TITLE);

        $reward->expects($this->once())
            ->method('getSlug')
            ->willReturn(self::REWARD_SLUG);

        $reward->expects($token ? $this->once() : $this->never())
            ->method('getToken')
            ->willReturn($token ?: $this->mockToken());

        return $reward;
    }

    protected function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->method('getRepository')
            ->willReturn($this->mockCurrencies(true));
        $moneyWrapper->method('format')
            ->willReturnCallback(function (Money $money) {
                return $money->getAmount() . ' ' . $money->getCurrency()->getCode();
            });

        return $moneyWrapper;
    }

    private function mockCurrencies(bool $isCurrencyExist): Currencies
    {
        $currencies = $this->createMock(Currencies::class);
        $currencies->method('contains')
            ->willReturn($isCurrencyExist);

        return $currencies;
    }

    private function mockTokenImage(): Image
    {
        $image = $this->createMock(Image::class);
        $image->method('getUrl')
            ->willReturn(self::TOKEN_AVATAR);

        return $image;
    }
}
