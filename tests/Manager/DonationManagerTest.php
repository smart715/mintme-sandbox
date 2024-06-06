<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Config\LimitHistoryConfig;
use App\Entity\Donation;
use App\Entity\User;
use App\Manager\DonationManager;
use App\Repository\DonationRepository;
use App\Tests\Mocks\MockMoneyWrapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DonationManagerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testGetUserRelated(): void
    {
        $user = $this->mockUser();
        $offset = 0;
        $limit = 10;
        $donations = [$this->mockDonation()];

        $donationRepository = $this->mockDonationRepository();
        $donationRepository
            ->expects($this->once())
            ->method('findUserRelated')
            ->with($user, $offset, $limit)
            ->willReturn($donations);

        $limitHistoryConfig = $this->createMock(LimitHistoryConfig::class);
        $limitHistoryConfig
            ->expects($this->once())
            ->method('getFromDate');

        $donationManager = new DonationManager($donationRepository, $limitHistoryConfig, $this->mockMoneyWrapper());

        $this->assertEquals($donations, $donationManager->getUserRelated($user, $offset, $limit));
    }

    /** @return MockObject|User */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    /** @return MockObject|Donation */
    private function mockDonation(): Donation
    {
        return $this->createMock(Donation::class);
    }

    /** @return MockObject|DonationRepository */
    private function mockDonationRepository(): DonationRepository
    {
        return $this->createMock(DonationRepository::class);
    }
}
