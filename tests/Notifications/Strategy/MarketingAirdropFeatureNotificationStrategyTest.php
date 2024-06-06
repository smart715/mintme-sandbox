<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Notifications\Strategy\MarketingAirdropFeatureNotificationStrategy;
use PHPUnit\Framework\TestCase;

class MarketingAirdropFeatureNotificationStrategyTest extends TestCase
{
    public function testSendNotification(): void
    {
        $tokens = [$this->mockToken()];
        $user = $this->mockUser($tokens);
        $notificationStrategy = new MarketingAirdropFeatureNotificationStrategy(
            $this->mockMailer($tokens[0])
        );

        $notificationStrategy->sendNotification($user);
    }

    public function testSendNotificationFailsIfNoTokens(): void
    {
        $user = $this->mockUser();
        $notificationStrategy = new MarketingAirdropFeatureNotificationStrategy(
            $this->mockMailer()
        );

        $notificationStrategy->sendNotification($user);
    }

    private function mockMailer(?Token $token = null): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);


        $mailer->expects($token ? $this->once() : $this->never())
            ->method('sendAirdropFeatureMail')
            ->with($token);

        return $mailer;
    }

    private function mockUser(array $tokens = []): User
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getTokens')
            ->willReturn($tokens);

        return $user;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }
}
