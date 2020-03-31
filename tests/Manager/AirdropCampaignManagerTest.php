<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\AirdropCampaignManager;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Tests\MockMoneyWrapper;
use App\Wallet\Money\MoneyWrapper;
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
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->exactly(2))->method('flush');
        /** @var User|MockObject $em */
        $user = $this->createMock(User::class);
        /** @var Profile|MockObject $em */
        $profile = $this->createMock(Profile::class);
        $profile
            ->method('getUser')
            ->willReturn($user);
        /** @var Token|MockObject $em */
        $token = $this->createMock(Token::class);
        $token
            ->method('getProfile')
            ->willReturn($profile);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);
        $amount = new Money(500, new Currency(MoneyWrapper::TOK_SYMBOL));

        $airdrop = $airdropManager->createAirdrop(
            $token,
            $amount,
            150
        );

        $this->assertInstanceOf(Airdrop::class, $airdrop);
        $this->assertEquals('500', $airdrop->getAmount());
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
        $this->assertEquals('700', $airdrop->getAmount());
        $this->assertEquals(300, $airdrop->getParticipants());
        $this->assertEquals($endDate, $airdrop->getEndDate());
    }

    public function testDeleteAirdrop(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);
        /** @var Token|MockObject $em */
        $token = $this->createMock(Token::class);

        $airdrop = new Airdrop();
        $airdrop
            ->setToken($token)
            ->setAmount('0')
            ->setActualAmount('0')
            ->setStatus(Airdrop::STATUS_ACTIVE);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);
        $airdropManager->deleteAirdrop($airdrop);

        $this->assertEquals(Airdrop::STATUS_REMOVED, $airdrop->getStatus());
    }

    public function testDeleteActiveAirdrop(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        /** @var Token|MockObject $em */
        $token = $this->createMock(Token::class);
        $airdrop = new Airdrop();
        $airdrop
            ->setAmount('0')
            ->setActualAmount('0')
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
            ->method('getParticipantByUserAndToken')
            ->willReturn(new AirdropParticipant());

        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        /** @var Token|MockObject $em */
        $token = $this->createMock(Token::class);
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        /** @var BalanceHandlerInterface|MockObject $bh */
        $bh = $this->createMock(BalanceHandlerInterface::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper(), $bh);

        $this->assertFalse($airdropManager->checkIfUserClaimed(null, $token));
        $this->assertFalse($airdropManager->checkIfUserClaimed($user, $token));

        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        $token->expects($this->exactly(2))->method('getActiveAirdrop')->willReturn($airdrop);

        $this->assertTrue($airdropManager->checkIfUserClaimed($user, $token));
    }
}
