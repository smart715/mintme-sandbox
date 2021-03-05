<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Activity\AirdropClaimedActivity;
use App\Entity\Activity\AirdropCreatedActivity;
use App\Entity\Activity\AirdropEndedActivity;
use App\Entity\Activity\DonationActivity;
use App\Entity\Activity\NewPostActivity;
use App\Entity\Activity\TokenCreatedActivity;
use App\Entity\Activity\TokenDeployedActivity;
use App\Entity\Activity\TokenDepositedActivity;
use App\Entity\Activity\TokenTradedActivity;
use App\Entity\Activity\TokenWithdrawnActivity;
use App\Manager\ActivityManager;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ActivityManagerTest extends TestCase
{
    public function testGetLast(): void
    {
        $a0 = new AirdropClaimedActivity();
        $a1 = new AirdropCreatedActivity();
        $a2 = new AirdropEndedActivity();
        $a3 = new DonationActivity();
        $a4 = new NewPostActivity();
        $a5 = new TokenCreatedActivity();
        $a6 = new TokenDeployedActivity();
        $a7 = new TokenDepositedActivity();
        $a8 = new TokenTradedActivity();
        $a9 = new TokenWithdrawnActivity();

        $activities = [$a0, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8, $a9];

        $ar = $this->createMock(ActivityRepository::class);
        $ar->expects($this->once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], 9)
            ->willReturn($activities);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(5))
            ->method('refresh')
            ->withConsecutive([$a0], [$a3], [$a7], [$a8], [$a9])
            ->willReturnOnConsecutiveCalls($a0, $a3, $a7, $a8, $a9);

        $am = new ActivityManager($em, $ar);
        $this->assertEquals($activities, $am->getLast(9));
    }
}
