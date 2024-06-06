<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Manager\BlacklistManagerInterface;
use App\Manager\PhoneNumberManager;
use App\Repository\PhoneNumberRepository;
use libphonenumber\PhoneNumber as LibphonenumberPhoneNumber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PhoneNumberManagerTest extends TestCase
{
    public function testGetPhoneNumber(): void
    {
        $profile = $this->mockProfile();
        $phoneNumber = $this->mockPhoneNumber();

        $phoneNumberRepository = $this->mockPhoneNumberRepository();
        $phoneNumberRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['profile' => $profile])
            ->willReturnOnConsecutiveCalls($phoneNumber, null);

        $phoneNumberManager = new PhoneNumberManager($phoneNumberRepository, $this->mockBlacklistManager());

        $this->assertEquals($phoneNumber, $phoneNumberManager->getPhoneNumber($profile));
        $this->assertNull($phoneNumberManager->getPhoneNumber($profile));
    }

    public function testFindByPhoneNumber(): void
    {
        $libphonenumberPhoneNumber = $this->mockLibphonenumberPhoneNumber();
        $phoneNumber = $this->mockPhoneNumber();

        $phoneNumberRepository = $this->mockPhoneNumberRepository();
        $phoneNumberRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['phoneNumber' => $libphonenumberPhoneNumber])
            ->willReturnOnConsecutiveCalls($phoneNumber, null);

        $phoneNumberManager = new PhoneNumberManager($phoneNumberRepository, $this->mockBlacklistManager());

        $this->assertEquals($phoneNumber, $phoneNumberManager->findByPhoneNumber($libphonenumberPhoneNumber));
        $this->assertNull($phoneNumberManager->findByPhoneNumber($libphonenumberPhoneNumber));
    }

    public function testFindVerifiedPhoneNumber(): void
    {
        $libphonenumberPhoneNumber = $this->mockLibphonenumberPhoneNumber();
        $phoneNumber = $this->mockPhoneNumber();

        $phoneNumberRepository = $this->mockPhoneNumberRepository();
        $phoneNumberRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['phoneNumber' => $libphonenumberPhoneNumber, 'verified' => true])
            ->willReturnOnConsecutiveCalls($phoneNumber, null);

        $phoneNumberManager = new PhoneNumberManager($phoneNumberRepository, $this->mockBlacklistManager());

        $this->assertEquals($phoneNumber, $phoneNumberManager->findVerifiedPhoneNumber($libphonenumberPhoneNumber));
        $this->assertNull($phoneNumberManager->findVerifiedPhoneNumber($libphonenumberPhoneNumber));
    }

    public function testFindAllVerified(): void
    {
        $phoneNumber = $this->mockPhoneNumber();

        $phoneNumberRepository = $this->mockPhoneNumberRepository();
        $phoneNumberRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['verified' => true])
            ->willReturn([$phoneNumber]);

        $phoneNumberManager = new PhoneNumberManager($phoneNumberRepository, $this->mockBlacklistManager());

        $this->assertEquals([$phoneNumber], $phoneNumberManager->findAllVerified());
    }

    public function testFindByCode(): void
    {
        $code = 'TEST';
        $phoneNumber = $this->mockPhoneNumber();

        $phoneNumberRepository = $this->mockPhoneNumberRepository();
        $phoneNumberRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['verificationCode' => $code])
            ->willReturnOnConsecutiveCalls($phoneNumber, null);

        $phoneNumberManager = new PhoneNumberManager($phoneNumberRepository, $this->mockBlacklistManager());

        $this->assertEquals($phoneNumber, $phoneNumberManager->findByCode($code));
        $this->assertNull($phoneNumberManager->findByCode($code));
    }

    public function testIsPhoneNumberBlacklisted(): void
    {
        $libphonenumberPhoneNumber = $this->mockLibphonenumberPhoneNumber();

        $blacklistManager = $this->mockBlacklistManager();
        $blacklistManager
            ->expects($this->exactly(2))
            ->method('isBlackListedNumber')
            ->with($libphonenumberPhoneNumber)
            ->willReturnOnConsecutiveCalls(true, false);

        $phoneNumberManager = new PhoneNumberManager($this->mockPhoneNumberRepository(), $blacklistManager);

        $this->assertTrue($phoneNumberManager->isPhoneNumberBlacklisted($libphonenumberPhoneNumber));
        $this->assertFalse($phoneNumberManager->isPhoneNumberBlacklisted($libphonenumberPhoneNumber));
    }

    /** @return MockObject|PhoneNumber */
    private function mockPhoneNumber(): PhoneNumber
    {
        return $this->createMock(PhoneNumber::class);
    }

    /** @return MockObject|Profile */
    private function mockProfile(): Profile
    {
        return $this->createMock(Profile::class);
    }

    /** @return MockObject|PhoneNumberRepository */
    private function mockPhoneNumberRepository(): PhoneNumberRepository
    {
        return $this->createMock(PhoneNumberRepository::class);
    }

    /** @return MockObject|LibphonenumberPhoneNumber */
    private function mockLibphonenumberPhoneNumber(): LibphonenumberPhoneNumber
    {
        return $this->createMock(LibphonenumberPhoneNumber::class);
    }

    /** @return MockObject|BlacklistManagerInterface */
    private function mockBlacklistManager(): BlacklistManagerInterface
    {
        return $this->createMock(BlacklistManagerInterface::class);
    }
}
