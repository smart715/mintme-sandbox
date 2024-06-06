<?php declare(strict_types = 1);

namespace App\Tests\Manager\PromotionHistory;

use App\Config\LimitHistoryConfig;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\PromotionHistory\PromotionHistoryTableManager;
use App\Repository\RewardParticipantRepository;
use PHPUnit\Framework\TestCase;

class PromotionHistoryTableManagerTest extends TestCase
{
    public function testGetCurrentElement(): void
    {
        $perPage = 10;

        $rp0 = new RewardParticipant();
        $rp1 = new RewardParticipant();
        $rp2 = new RewardParticipant();

        $rp0->setNote('0');
        $rp1->setNote('1');
        $rp2->setNote('2');

        $rewardParticipants = [$rp0, $rp1, $rp2];

        $user = new User();
        $rpr = $this->createMock(RewardParticipantRepository::class);

        $rpr->expects($this->once())
            ->method('getPromotionHistoryByUserAndToken')
            ->with($user, 0, $perPage)
            ->willReturn($rewardParticipants);

        $phtm = new PromotionHistoryTableManager($rpr, $user, $this->limitHistoryConfigMock(), $perPage);
        $this->assertEquals($rp0, $phtm->getCurrentElement());
    }

    public function testNextElement(): void
    {
        $perPage = 3;

        $rp0 = new RewardParticipant();
        $rp1 = new RewardParticipant();
        $rp2 = new RewardParticipant();
        $rp3 = new RewardParticipant();
        $rp4 = new RewardParticipant();
        $rp5 = new RewardParticipant();

        $rp0->setNote('0');
        $rp1->setNote('1');
        $rp2->setNote('2');
        $rp3->setNote('3');
        $rp4->setNote('4');
        $rp5->setNote('5');

        $rewardParticipants0 = [$rp0, $rp1, $rp2];
        $rewardParticipants1 = [$rp3, $rp4, $rp5];

        $user = new User();
        $rpr = $this->createMock(RewardParticipantRepository::class);

        $rpr->expects($this->exactly(3))
            ->method('getPromotionHistoryByUserAndToken')
            ->withConsecutive(
                [$user, 0, $perPage],
                [$user, $perPage, $perPage],
                [$user, 2 * $perPage, $perPage]
            )
            ->willReturnOnConsecutiveCalls(
                $rewardParticipants0,
                $rewardParticipants1,
                []
            );

        $phtm = new PromotionHistoryTableManager($rpr, $user, $this->limitHistoryConfigMock(), $perPage);
        $this->assertEquals($rp0, $phtm->getCurrentElement());

        $phtm->nextElement();
        $this->assertEquals($rp1, $phtm->getCurrentElement());

        $phtm->nextElement();
        $this->assertEquals($rp2, $phtm->getCurrentElement());

        $phtm->nextElement();
        $this->assertEquals($rp3, $phtm->getCurrentElement());

        $phtm->nextElement();
        $this->assertEquals($rp4, $phtm->getCurrentElement());

        $phtm->nextElement();
        $this->assertEquals($rp5, $phtm->getCurrentElement());

        $phtm->nextElement();
        $this->assertEquals(null, $phtm->getCurrentElement());
    }

    private function limitHistoryConfigMock(): LimitHistoryConfig
    {
        return $this->createMock(LimitHistoryConfig::class);
    }
}
