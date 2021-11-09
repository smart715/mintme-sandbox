<?php declare(strict_types = 1);

namespace App\Tests\EventListener;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropReferralCode;
use App\Entity\User;
use App\EventListener\RegistrationCompletedListener;
use App\Logger\UserActionLogger;
use App\Manager\AirdropReferralCodeManagerInterface;
use App\Manager\UserManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class RegistrationCompletedListenerTest extends TestCase
{
    public function testOnFosuserRegistrationCompletedWithReferencer(): void
    {
        $u = $this->createMock(User::class);
        $u->expects($this->once())->method('setReferencer');
        $u->expects($this->never())->method('setAirdropReferrer')->willReturnSelf();
        $u->expects($this->never())->method('setAirdropReferrerUser');

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
        $request->cookies->expects($this->at(0))->method('get')->with('referral-type')->willReturn('invite');
        $request->cookies->expects($this->at(1))->method('get')->with('referral-code')->willReturn('');

        $event = $this->createMock(FilterUserResponseEvent::class);
        $event->method('getUser')->willReturn($u);
        $event->method('getRequest')->willReturn($request);

        $um = $this->createMock(UserManagerInterface::class);
        $um->method('findByReferralCode')->willReturn($this->createMock(User::class));
        $um->expects($this->exactly(2))->method('updateUser')->with($u);

        $listener = new RegistrationCompletedListener(
            $um,
            $this->createMock(UserActionLogger::class),
            $this->createMock(AirdropReferralCodeManagerInterface::class)
        );

        $listener->onFosuserRegistrationCompleted($event);
    }

    public function testOnFosuserRegistrationCompletedWithAirdropReferral(): void
    {
        $u = $this->createMock(User::class);
        $u->expects($this->never())->method('setReferencer');
        $u->expects($this->once())->method('setAirdropReferrer')->willReturnSelf();
        $u->expects($this->once())->method('setAirdropReferrerUser');

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
        $request->cookies->expects($this->at(0))->method('get')->with('referral-type')->willReturn('airdrop');
        $request->cookies->expects($this->at(1))->method('get')->with('referral-code')->willReturn('');

        $event = $this->createMock(FilterUserResponseEvent::class);
        $event->method('getUser')->willReturn($u);
        $event->method('getRequest')->willReturn($request);

        $um = $this->createMock(UserManagerInterface::class);
        $um->expects($this->exactly(2))->method('updateUser')->with($u);

        $arc = $this->createMock(AirdropReferralCode::class);
        $arc->method('getUser')->willReturn($this->createMock(User::class));
        $arc->method('getAirdrop')->willReturn($this->createMock(Airdrop::class));

        $arcm = $this->createMock(AirdropReferralCodeManagerInterface::class);
        $arcm->method('decode')->willReturn($arc);

        $listener = new RegistrationCompletedListener(
            $um,
            $this->createMock(UserActionLogger::class),
            $arcm
        );

        $listener->onFosuserRegistrationCompleted($event);
    }

    public function testOnFosuserRegistrationCompletedWithoutAnyreferral(): void
    {
        $u = $this->createMock(User::class);
        $u->expects($this->never())->method('setReferencer');
        $u->expects($this->never())->method('setAirdropReferrer')->willReturnSelf();
        $u->expects($this->never())->method('setAirdropReferrerUser');

        $request = $this->createMock(Request::class);
        $request->cookies = $this->createMock(ParameterBag::class);
        $request->cookies->expects($this->once())->method('get')->with('referral-type')->willReturn(null);

        $event = $this->createMock(FilterUserResponseEvent::class);
        $event->method('getUser')->willReturn($u);
        $event->method('getRequest')->willReturn($request);

        $um = $this->createMock(UserManagerInterface::class);
        $um->expects($this->once())->method('updateUser')->with($u);

        $listener = new RegistrationCompletedListener(
            $um,
            $this->createMock(UserActionLogger::class),
            $this->createMock(AirdropReferralCodeManagerInterface::class)
        );

        $listener->onFosuserRegistrationCompleted($event);
    }
}
