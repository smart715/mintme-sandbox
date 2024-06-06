<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropReferralCode;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\TokenEvents;
use App\Events\UserAirdropEvent;
use App\EventSubscriber\AirdropClaimedSubscriber;
use App\Mailer\MailerInterface;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\AirdropReferralCodeManager;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AirdropClaimedSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testSendMailForAirdropClaimed(): void
    {
        $subscriber = new AirdropClaimedSubscriber(
            $this->mockMailer($this->once()),
            $this->mockAirdropReferralCodeManager($this->once(), false),
            $this->mockAirdropCampaignManager($this->never())
        );
        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockUserAirdropEvent();

        $this->dispatcher->dispatch($event, TokenEvents::AIRDROP_CLAIMED);
    }

    public function testSendMailForAirdropClaimedWithAlreadyExistingReferralCode(): void
    {
        $subscriber = new AirdropClaimedSubscriber(
            $this->mockMailer($this->once()),
            $this->mockAirdropReferralCodeManager($this->once(), true),
            $this->mockAirdropCampaignManager($this->never())
        );
        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockUserAirdropEvent();

        $this->dispatcher->dispatch($event, TokenEvents::AIRDROP_CLAIMED);
    }

    public function testClaimedAirdropFromSessionData(): void
    {
        $subscriber = new AirdropClaimedSubscriber(
            $this->mockMailer($this->never()),
            $this->mockAirdropReferralCodeManager($this->never()),
            $this->mockAirdropCampaignManager($this->once())
        );
        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockInteractiveLoginEvent();

        $this->dispatcher->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);
    }

    private function mockMailer(InvokedCount $invokedCount): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($invokedCount)->method('sendAirdropClaimedMail');

        return $mailer;
    }

    private function mockAirdropCampaignManager(InvokedCount $invokedCount): AirdropCampaignManagerInterface
    {
        $AirdropReferralCodeManager = $this->createMock(AirdropCampaignManagerInterface::class);

        $AirdropReferralCodeManager->expects($invokedCount)->method('claimAirdropsActionsFromSessionData');

        return $AirdropReferralCodeManager;
    }

    private function mockAirdropReferralCodeManager(
        InvokedCount $invokedCount,
        ?bool $referralCodeExist = null
    ): AirdropReferralCodeManager {
        $airdropCampaignManager = $this->createMock(AirdropReferralCodeManager::class);
        $airdropCampaignManager->expects($invokedCount)
            ->method('getByAirdropAndUser')
            ->willReturn($referralCodeExist ? $this->mockAirdropReferralCode() : null);

        if (null !== $referralCodeExist) {
            $airdropCampaignManager->expects(!$referralCodeExist?
                $this->once() : $this->never())
                ->method('create')
                ->willReturn($this->mockAirdropReferralCode());
        }

        $airdropCampaignManager->method('encode')->willReturn('TEST');

        return $airdropCampaignManager;
    }

    private function mockUserAirdropEvent(): UserAirdropEvent
    {
        $userAirdropEvent = $this->createMock(UserAirdropEvent::class);
        $userAirdropEvent->expects($this->once())->method('getUser')->willReturn($this->mockUser());
        $userAirdropEvent->expects($this->once())->method('getToken')->willReturn($this->mockToken());
        $userAirdropEvent->expects($this->once())->method('getAirdrop')->willReturn($this->mockAirdrop());

        return $userAirdropEvent;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockAirdrop(): Airdrop
    {
        $airdrop = $this->createMock(Airdrop::class);
        $airdrop->expects($this->once())
            ->method('getReward')
            ->willReturn(new Money(100, new Currency('TEST')));

        return $airdrop;
    }

    private function mockAirdropReferralCode(): AirdropReferralCode
    {
        return $this->createMock(AirdropReferralCode::class);
    }

    private function mockInteractiveLoginEvent(): InteractiveLoginEvent
    {
        $interactiveLoginEvent = $this->createMock(InteractiveLoginEvent::class);

        $interactiveLoginEvent->expects($this->once())
            ->method('getAuthenticationToken')
            ->willReturn($this->mockTokenInterface());

        return $interactiveLoginEvent;
    }

    private function mockTokenInterface(): TokenInterface
    {
        $tokenInterface = $this->createMock(TokenInterface::class);
        $tokenInterface->expects($this->once())->method('getUser')->willReturn($this->mockUser());

        return $tokenInterface;
    }
}
