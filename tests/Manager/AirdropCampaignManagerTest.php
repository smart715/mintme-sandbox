<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\AirdropCampaignManager;
use App\Manager\TokenManagerInterface;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\AirdropCampaign\AirdropRepository;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Symbols;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AirdropCampaignManagerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testCreateAirdrop(): void
    {
        $em = $this->mockEntityManager();
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->exactly(2))->method('flush');
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));
        /** @var User|MockObject */
        $user = $this->createMock(User::class);
        /** @var Profile|MockObject */
        $profile = $this->createMock(Profile::class);
        $profile
            ->method('getUser')
            ->willReturn($user);
        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);
        $token
            ->method('getProfile')
            ->willReturn($profile);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh
            ->method('exchangeBalance')
            ->with($user, $token)
            ->willReturn(new Money(100000000000, new Currency(Symbols::TOK)));

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );
        $amount = new Money(500, new Currency(Symbols::TOK));

        $airdrop = $airdropManager->createAirdrop(
            $token,
            $amount,
            150
        );

        $this->assertInstanceOf(Airdrop::class, $airdrop);
        $this->assertEquals('500', $airdrop->getAmount()->getAmount());
        $this->assertEquals(150, $airdrop->getParticipants());
        $this->assertEquals(null, $airdrop->getEndDate());

        $amount = new Money(700, new Currency(Symbols::TOK));
        $endDate = new \DateTimeImmutable('+2 days');
        $airdrop = $airdropManager->createAirdrop(
            $token,
            $amount,
            300,
            $endDate
        );

        $this->assertInstanceOf(Airdrop::class, $airdrop);
        $this->assertEquals('700', $airdrop->getAmount()->getAmount());
        $this->assertEquals(300, $airdrop->getParticipants());
        $this->assertNotNull($airdrop->getEndDate());
    }

    public function testDeleteAirdrop(): void
    {
        $em = $this->mockEntityManager();
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));
        /** @var BalanceHandlerInterface|MockObject */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);

        $airdrop = new Airdrop();
        $airdrop
            ->setToken($token)
            ->setAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setActualAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setLockedAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setStatus(Airdrop::STATUS_ACTIVE);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );
        $airdropManager->deleteAirdrop($airdrop);

        $this->assertEquals(Airdrop::STATUS_REMOVED, $airdrop->getStatus());
    }

    public function testDeleteActiveAirdrop(): void
    {
        $em = $this->mockEntityManager();
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));
        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);
        $airdrop = new Airdrop();
        $airdrop
            ->setAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setActualAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setLockedAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setStatus(Airdrop::STATUS_ACTIVE)
            ->setToken($token);

        $token->expects($this->once())->method('getActiveAirdrop')->willReturn($airdrop);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );
        $airdropManager->deleteActiveAirdrop($token);

        $this->assertEquals(Airdrop::STATUS_REMOVED, $airdrop->getStatus());
    }

    public function testShowAirdropCampaign(): void
    {
        /** @var AirdropParticipantRepository|MockObject $repository */
        $repository = $this->createMock(AirdropParticipantRepository::class);
        $repository
            ->expects($this->once())
            ->method('getParticipantByUserAndAirdrop')
            ->willReturn(new AirdropParticipant());

        /** @var EntityManagerInterface|MockObject $repository */
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->at(0))
            ->method('getRepository')
            ->with(AirdropParticipant::class)
            ->willReturn($repository);

        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));

        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        /** @psalm-suppress InvalidArgument */
        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );

        $this->assertFalse($airdropManager->checkIfUserClaimed(null, $token));
        $this->assertFalse($airdropManager->checkIfUserClaimed($user, $token));

        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        $token->expects($this->exactly(2))->method('getActiveAirdrop')->willReturn($airdrop);

        $this->assertTrue($airdropManager->checkIfUserClaimed($user, $token));
    }

    public function testClaimAirdropCampaign(): void
    {
        $em = $this->mockEntityManager();
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->once())->method('flush');
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->once())->method('depositBonus');
        /** @var User|MockObject */
        $owner = $this->createMock(User::class);
        /** @var Profile|MockObject */
        $profile = $this->createMock(Profile::class);
        $profile
            ->method('getUser')
            ->willReturn($owner);
        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);
        $token
            ->method('getProfile')
            ->willReturn($profile);
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $bh
            ->method('exchangeBalance')
            ->with($user, $token)
            ->willReturn(new Money(100000000000, new Currency(Symbols::TOK)));

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );

        $airdrop = new Airdrop();
        $airdrop->setAmount(new Money(100, new Currency(Symbols::TOK)));
        $airdrop->setParticipants(100);
        $airdrop->setLockedAmount(new Money(100, new Currency(Symbols::TOK)));

        $token->expects($this->once())->method('getActiveAirdrop')->willReturn($airdrop);

        $airdropManager->claimAirdropCampaign($user, $token);

        $this->assertEquals('1', $airdrop->getActualAmount()->getAmount());
        $this->assertEquals(1, $airdrop->getActualParticipants());
    }

    public function testGetAirdropReward(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropParticipantRepository::class));
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );

        $airdrop = new Airdrop();
        $airdrop->setAmount(new Money(0, new Currency(Symbols::TOK)));
        $airdrop->setParticipants(10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Airdrop reward calculation failed.');
        $airdropManager->getAirdropReward($airdrop);

        $airdrop->setAmount(new Money(100, new Currency(Symbols::TOK)));
        $airdrop->setParticipants(0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Airdrop reward calculation failed.');
        $airdropManager->getAirdropReward($airdrop);

        $airdrop->setAmount(new Money(100, new Currency(Symbols::TOK)));
        $airdrop->setParticipants(100);

        $reward = $airdropManager->getAirdropReward($airdrop);
        $this->assertEquals('1', $reward->getAmount());
    }

    public function testUpdateOutdatedAirdrops(): void
    {
        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);
        $airdrops = [
            (new Airdrop())->setToken($token),
            (new Airdrop())->setToken($token),
        ];

        /** @var AirdropRepository|MockObject $repository */
        $repository = $this->createMock(AirdropRepository::class);
        $repository
            ->expects($this->once())
            ->method('getOutdatedAirdrops')
            ->willReturn($airdrops);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->mockEntityManager();
        $em
            ->expects($this->at(1))
            ->method('getRepository')
            ->with(Airdrop::class)
            ->willReturn($repository);

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );

        $countUpdated = $airdropManager->updateOutdatedAirdrops();

        $this->assertEquals(2, $countUpdated);
        $this->assertEquals(Airdrop::STATUS_REMOVED, $airdrops[0]->getStatus());
        $this->assertEquals(Airdrop::STATUS_REMOVED, $airdrops[1]->getStatus());
    }

    public function testCreateAction(): void
    {
        $airdrop = $this->createMock(Airdrop::class);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropParticipantRepository::class));
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));

        $em->expects($this->exactly(9))
            ->method('persist')
            ->withConsecutive(
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 0 === $action->getType() && 'twitterMessage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 1 === $action->getType() && 'twitterRetweet' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 2 === $action->getType() && 'facebookMessage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 3 === $action->getType() && 'facebookPage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 4 === $action->getType() && 'facebookPost' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 5 === $action->getType() && 'linkedinMessage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 6 === $action->getType() && 'youtubeSubscribe' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 7 === $action->getType() && 'postLink' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 8 === $action->getType() && 'visitExternalUrl' === $action->getData())]
            );

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );

        foreach (array_keys(AirdropAction::TYPE_MAP) as $key) {
            $airdropManager->createAction($key, $key, $airdrop);
        }
    }

    public function testClaimAirdropAction(): void
    {
        $user = $this->createMock(User::class);
        $action = $this->createMock(AirdropAction::class);
        $action->expects($this->once())->method('addUser')->with($user);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->mockEntityManager();
        $em->expects($this->once())->method('persist')->with($action);
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );

        $airdropManager->claimAirdropAction($action, $user);
    }

    public function testCheckIfUserCompletedActions(): void
    {
        $user = $this->createMock(User::class);
        $usersCollection = new ArrayCollection([]);

        $action = $this->createMock(AirdropAction::class);
        $action->method('getUsers')->willReturn($usersCollection);

        $actionsCollection = new ArrayCollection([$action]);

        $airdrop = $this->createMock(Airdrop::class);
        $airdrop->method('getActions')->willReturn($actionsCollection);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropParticipantRepository::class));
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $this->mockSession()
        );

        $this->assertFalse($airdropManager->checkIfUserCompletedActions($airdrop, $user));

        $usersCollection->add($user);

        $this->assertTrue($airdropManager->checkIfUserCompletedActions($airdrop, $user));
    }

    public function testNotClaimAirdropActionFromSessionDataOnEmptySession(): void
    {
        $user = $this->createMock(User::class);
        $session = $this->createMock(SessionInterface::class);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropParticipantRepository::class));
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager(),
            $session
        );

        $this->assertEquals(null, $session->get('airdrops'));
        $session->expects(self::never())->method('remove')->with('airdrops');
        $airdropManager->claimAirdropsActionsFromSessionData($user);
    }

    public function testNotClaimAirdropActionOnUserAlreadyClaimed(): void
    {
        $user = $this->createMock(User::class);
        $session = $this->createMock(SessionInterface::class);
        $action = $this->createMock(AirdropAction::class);
        $airdrop = $this->createMock(Airdrop::class);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $airdropParticipantRepo = $this->createMock(AirdropParticipantRepository::class);
        $airdropParticipantRepo
            ->method('getParticipantByUserAndAirdrop')
            ->willReturn(new AirdropParticipant());

        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($airdropParticipantRepo);
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $token = $this->createMock(Token::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager($token, $this->once()),
            $session
        );

        /** @var Token|MockObject */
        $sessionData = [$token->getName() =>[$action->getId()]];
        $session->expects($this->once())->method('get')->with('airdrops')->willReturn($sessionData);
        $token->expects(self::exactly(4))->method('getActiveAirdrop')->willReturn($airdrop);
        $this->assertTrue($airdropManager->checkIfUserClaimed($user, $token));
        $session->expects(self::once())->method('remove')->with('airdrops');
        $airdropManager->claimAirdropsActionsFromSessionData($user);
    }

    public function testNotClaimAirdropActionOnAirdropRemoved(): void
    {
        $user = $this->createMock(User::class);
        $session = $this->createMock(SessionInterface::class);
        $action = $this->createMock(AirdropAction::class);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropParticipantRepository::class));
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));
        $airdropRepo = $this->createMock(ObjectRepository::class);
        $em->expects($this->at(2))
            ->method('getRepository')
            ->willReturn($airdropRepo);


        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $token = $this->createMock(Token::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager($token, $this->once()),
            $session
        );

        $airdrop = new Airdrop();
        $airdrop
            ->setToken($token)
            ->setAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setActualAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setLockedAmount(new Money(0, new Currency(Symbols::TOK)))
            ->setStatus(Airdrop::STATUS_ACTIVE);
        $airdropAction = $this->createMock(AirdropAction::class);
        $airdropAction->method('getAirdrop')->willReturn($airdrop);
        $collection = $this->createMock(Collection::class);
        $collection->method('getValues')->willReturn([]);
        $airdropAction->method('getUsers')->willReturn($collection);
        $airdropRepo->expects($this->once())->method('find')->willReturn($airdropAction);

        $airdropManager->deleteActiveAirdrop($token);

        $sessionData = [$token->getName() =>[$action->getId()]];
        $session->expects($this->once())->method('get')->with('airdrops')->willReturn($sessionData);
        $token->expects(self::exactly(2))->method('getActiveAirdrop')->willReturn($airdrop);
        $this->assertEquals(Airdrop::STATUS_ACTIVE, $airdrop->getStatus());
        $session->expects(self::once())->method('remove')->with('airdrops');
        $airdropManager->claimAirdropsActionsFromSessionData($user);
        $action->expects(self::never())->method('addUser');
    }

    public function testNotClaimAirdropActionOnActionCompletedByUser(): void
    {
        $user = $this->createMock(User::class);
        $session = $this->createMock(SessionInterface::class);
        $action = $this->createMock(AirdropAction::class);

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropParticipantRepository::class));
        $em->expects($this->at(1))
            ->method('getRepository')
            ->willReturn($this->createMock(AirdropRepository::class));
        $airdropRepo = $this->createMock(ObjectRepository::class);
        $em->expects($this->at(2))
            ->method('getRepository')
            ->willReturn($airdropRepo);

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $token = $this->createMock(Token::class);

        $airdropManager = new AirdropCampaignManager(
            $em,
            $this->mockMoneyWrapper(),
            $bh,
            $this->mockEventDispatcher(),
            $this->mockTokenManager($token, $this->once()),
            $session
        );
        $airdropAction = $this->createMock(AirdropAction::class);
        $airdropAction->method('getAirdrop')->willReturn($this->createMock(Airdrop::class));
        $collection = $this->createMock(Collection::class);
        $collection->method('getValues')->willReturn([$user]);
        $airdropAction->method('getUsers')->willReturn($collection);
        $airdropRepo->expects($this->once())->method('find')->willReturn($airdropAction);

        /** @var Token|MockObject */
        $sessionData = [$token->getName() =>[$action->getId()]];
        $session->expects($this->once())->method('get')->with('airdrops')->willReturn($sessionData);
        $action->expects($this->never())->method('addUser')->with($user);
        $session->expects(self::once())->method('remove')->with('airdrops');

        $airdropManager->claimAirdropsActionsFromSessionData($user);
    }

    private function mockEventDispatcher(): EventDispatcherInterface
    {
        return $this->createMock(EventDispatcherInterface::class);
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManager(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $apr = $this->createMock(AirdropParticipantRepository::class);
        $em->expects($this->at(0))->method('getRepository')->with(AirdropParticipant::class)->willReturn($apr);

        return $em;
    }

    private function mockTokenManager(?Token $token = null, ?InvokedCount $invocation = null): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->expects($invocation ?? self::never())
            ->method('findByName')->willReturn($token);

        return $tm;
    }

    private function mockSession(bool $has = true, string $code = ''): SessionInterface
    {
        /** @var SessionInterface|MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('has')->willReturn($has);
        $session->method('get')->willReturn($code);

        return $session;
    }
}
