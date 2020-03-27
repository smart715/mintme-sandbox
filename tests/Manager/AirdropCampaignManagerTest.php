<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\AirdropCampaignManager;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Tests\MockMoneyWrapper;
use Doctrine\ORM\EntityManagerInterface;
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
        /** @var Token|MockObject $em */
        $token = $this->createMock(Token::class);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper());

        $airdrop = $airdropManager->createAirdrop(
            $token,
            '500',
            150
        );

        $this->assertInstanceOf(Airdrop::class, $airdrop);
        $this->assertEquals('500', $airdrop->getAmount());
        $this->assertEquals(150, $airdrop->getParticipants());
        $this->assertEquals(null, $airdrop->getEndDate());

        $endDate = new \DateTimeImmutable('+2 days');
        $airdrop = $airdropManager->createAirdrop(
            $token,
            '700',
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
        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper());
        $airdropManager->deleteAirdrop($airdrop);

        $this->assertEquals(Airdrop::STATUS_REMOVED, $airdrop->getStatus());
    }

    public function testDeleteActiveAirdrop(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');
        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        /** @var Token|MockObject $em */
        $token = $this->createMock(Token::class);
        $token->expects($this->once())->method('getActiveAirdrop')->willReturn($airdrop);

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper());
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

        $airdropManager = new AirdropCampaignManager($em, $this->mockMoneyWrapper());

        $this->assertFalse($airdropManager->checkIfUserClaimed(null, $token));
        $this->assertFalse($airdropManager->checkIfUserClaimed($user, $token));

        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        $token->expects($this->exactly(2))->method('getActiveAirdrop')->willReturn($airdrop);

        $this->assertTrue($airdropManager->checkIfUserClaimed($user, $token));
    }
}
