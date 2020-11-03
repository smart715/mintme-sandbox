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
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\AirdropCampaign\AirdropRepository;
use App\Tests\MockMoneyWrapper;
use App\Wallet\Money\MoneyWrapper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AirdropCampaignManagerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testCreateAirdrop(): void
    {
        /** @var EntityManagerInterface|MockObject */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->exactly(2))->method('flush');
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
            ->willReturn(new Money(100000000000, new Currency(Token::TOK_SYMBOL)));

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);
        $amount = new Money(500, new Currency(MoneyWrapper::TOK_SYMBOL));

        $airdrop = $airdropManager->createAirdrop(
            $token,
            $amount,
            150
        );

        $this->assertInstanceOf(Airdrop::class, $airdrop);
        $this->assertEquals('500', $airdrop->getAmount()->getAmount());
        $this->assertEquals(150, $airdrop->getParticipants());
        $this->assertEquals(null, $airdrop->getEndDate());

        $amount = new Money(700, new Currency(MoneyWrapper::TOK_SYMBOL));
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
        /** @var EntityManagerInterface|MockObject */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        /** @var BalanceHandlerInterface|MockObject */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);

        $airdrop = new Airdrop();
        $airdrop
            ->setToken($token)
            ->setAmount(new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setActualAmount(new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setLockedAmount(new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setStatus(Airdrop::STATUS_ACTIVE);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);
        $airdropManager->deleteAirdrop($airdrop);

        $this->assertEquals(Airdrop::STATUS_REMOVED, $airdrop->getStatus());
    }

    public function testDeleteActiveAirdrop(): void
    {
        /** @var EntityManagerInterface|MockObject */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);
        $airdrop = new Airdrop();
        $airdrop
            ->setAmount(new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setActualAmount(new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setLockedAmount(new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL)))
            ->setStatus(Airdrop::STATUS_ACTIVE)
            ->setToken($token);

        $token->expects($this->once())->method('getActiveAirdrop')->willReturn($airdrop);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);
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

        /** @var EntityManagerInterface|MockObject */
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        /** @var Token|MockObject */
        $token = $this->createMock(Token::class);
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        /** @psalm-suppress InvalidArgument */
        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

        $this->assertFalse($airdropManager->checkIfUserClaimed(null, $token));
        $this->assertFalse($airdropManager->checkIfUserClaimed($user, $token));

        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        $token->expects($this->exactly(2))->method('getActiveAirdrop')->willReturn($airdrop);

        $this->assertTrue($airdropManager->checkIfUserClaimed($user, $token));
    }

    public function testClaimAirdropCampaign(): void
    {
        /** @var EntityManagerInterface|MockObject */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->once())->method('flush');
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->once())->method('update');
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
            ->willReturn(new Money(100000000000, new Currency(Token::TOK_SYMBOL)));

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

        $airdrop = new Airdrop();
        $airdrop->setAmount(new Money(100, new Currency(MoneyWrapper::TOK_SYMBOL)));
        $airdrop->setParticipants(100);
        $airdrop->setLockedAmount(new Money(100, new Currency(MoneyWrapper::TOK_SYMBOL)));

        $token->expects($this->once())->method('getActiveAirdrop')->willReturn($airdrop);

        $airdropManager->claimAirdropCampaign($user, $token);

        $this->assertEquals('1', $airdrop->getActualAmount()->getAmount());
        $this->assertEquals(1, $airdrop->getActualParticipants());
    }

    public function testGetAirdropReward(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

        $airdrop = new Airdrop();
        $airdrop->setAmount(new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL)));
        $airdrop->setParticipants(10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Airdrop reward calculation failed.');
        $airdropManager->getAirdropReward($airdrop);

        $airdrop->setAmount(new Money(100, new Currency(MoneyWrapper::TOK_SYMBOL)));
        $airdrop->setParticipants(0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Airdrop reward calculation failed.');
        $airdropManager->getAirdropReward($airdrop);

        $airdrop->setAmount(new Money(100, new Currency(MoneyWrapper::TOK_SYMBOL)));
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
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->method('getRepository')
            ->willReturn($repository);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

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

        $em->expects($this->exactly(8))
            ->method('persist')
            ->withConsecutive(
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 0 === $action->getType() && 'twitterMessage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 1 === $action->getType() && 'twitterRetweet' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 2 === $action->getType() && 'facebookMessage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 3 === $action->getType() && 'facebookPage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 4 === $action->getType() && 'facebookPost' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 5 === $action->getType() && 'linkedinMessage' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 6 === $action->getType() && 'youtubeSubscribe' === $action->getData())],
                [$this->callback(fn ($action) => $action->getAirdrop() === $airdrop && 7 === $action->getType() && 'postLink' === $action->getData())]
            );

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

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
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($action);

        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

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
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

        $this->assertFalse($airdropManager->checkIfUserCompletedActions($airdrop, $user));

        $usersCollection->add($user);

        $this->assertTrue($airdropManager->checkIfUserCompletedActions($airdrop, $user));
    }
}
