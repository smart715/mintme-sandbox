<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Config\PhoneNumberConfig;
use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Entity\User;
use App\Entity\ValidationCode\ValidationCode;
use App\Manager\ProfileManager;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumber as LibphonenumberPhoneNumber;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileManagerTest extends TestCase
{
    public function testFindProfileByHash(): void
    {
        $profile = $this->mockProfile();
        $user = $this->createMock(User::class);

        $manager = new ProfileManager(
            $this->mockEntityManager($profile, $user, false),
            $this->mockUserRepository($user),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $this->assertEquals($user, $manager->findProfileByHash('qwe'));
        $this->assertEquals(null, $manager->findProfileByHash(null));
        $this->assertEquals(null, $manager->findProfileByHash(''));
    }

    public function testGetProfile(): void
    {
        $profile = $this->mockProfile();
        $user = $this->createMock(User::class);

        $manager = new ProfileManager(
            $this->mockEntityManager($profile, $user, false),
            $this->createMock(UserRepository::class),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $this->assertEquals(null, $manager->getProfile(new stdClass()));
        $this->assertEquals(
            $this->createMock(Profile::class),
            $manager->getProfile($this->createMock(User::class))
        );
    }

    public function testFindByEmail(): void
    {
        $profile = $this->mockProfile();
        $user = $this->createMock(User::class);

        $manager = new ProfileManager(
            $this->mockEntityManager($profile, $user, false),
            $this->mockUserRepository($user),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $this->assertEquals($this->createMock(Profile::class), $manager->findByEmail('foo@bar.baz'));

        $manager = new ProfileManager(
            $this->mockEntityManager($profile, null, false),
            $this->createMock(UserRepository::class),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );
        $this->assertEquals(null, $manager->findByEmail('foo@bar.baz'));
    }

    public function testChangePhoneWithNewPhone(): void
    {
        $phoneNumber = $this->createMock(PhoneNumber::class);
        $validationCode = $this->createMock(ValidationCode::class);
        $oldPhoneNumber = new LibphonenumberPhoneNumber();
        $phoneNumber->expects($this->once())->method('getPhoneNumber')->willReturn($oldPhoneNumber);
        $phoneNumber->expects($this->exactly(2))->method('getSMSCode')->willReturn($validationCode);
        $phoneNumber->expects($this->exactly(1))->method('getMailCode')->willReturn($validationCode);
        $newPhoneNumber = new LibphonenumberPhoneNumber();


        $profile = $this->mockProfile();
        $profile->method('getPhoneNumber')->willReturn($phoneNumber);


        $user = $this->createMock(User::class);

        $phoneUtilsMock = $this->createMock(PhoneNumberUtil::class);

        $phoneUtilsMock
            ->expects($this->at(0))
            ->method('format')
            ->with($oldPhoneNumber, 0)
            ->willReturn("+1111111111");
        $phoneUtilsMock
            ->expects($this->at(1))
            ->method('format')
            ->with($newPhoneNumber, 0)
            ->willReturn("+2222222222");

        $emMock = $this->mockEntityManager($profile, $user, false);
        $emMock->expects($this->once())
            ->method('persist')
            ->with($profile);
        $emMock->expects($this->once())
            ->method('flush');

        $manager = new ProfileManager(
            $emMock,
            $this->mockUserRepository($user),
            $phoneUtilsMock,
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $manager->changePhone($profile, $newPhoneNumber);
    }

    public function testChangePhoneWithSamePhone(): void
    {
        $oldPhoneNumber = new LibphonenumberPhoneNumber();
        $newPhoneNumber = new LibphonenumberPhoneNumber();

        $profile = $this->mockProfile();
        $phoneNumber = $this->createMock(PhoneNumber::class);
        $validationCode = $this->createMock(ValidationCode::class);
        $profile->method('getPhoneNumber')->willReturn($phoneNumber);
        $phoneNumber->method('getPhoneNumber')->willReturn($oldPhoneNumber);
        $phoneNumber->expects($this->once())
            ->method('setTemPhoneNumber')
            ->with($newPhoneNumber);
        $phoneNumber->expects($this->exactly(2))
            ->method('getSMSCode')
            ->willReturn($validationCode);
        $phoneNumber->expects($this->exactly(1))
            ->method('getMailCode')
            ->willReturn($validationCode);

        $user = $this->createMock(User::class);

        $phoneUtilsMock = $this->createMock(PhoneNumberUtil::class);

        $phoneUtilsMock
            ->expects($this->at(0))
            ->method('format')
            ->with($oldPhoneNumber, 0)
            ->willReturn("+1111111111");
        $phoneUtilsMock
            ->expects($this->at(1))
            ->method('format')
            ->with($newPhoneNumber, 0)
            ->willReturn("+2222222222");

        $emMock = $this->mockEntityManager($profile, $user, false);
        $emMock->expects($this->once())
            ->method('persist')
            ->with($profile);
        $emMock->expects($this->once())
            ->method('flush');

        $manager = new ProfileManager(
            $emMock,
            $this->mockUserRepository($user),
            $phoneUtilsMock,
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $manager->changePhone($profile, $newPhoneNumber);
    }

    public function testChangePhoneWithoutNewPhone(): void
    {
        $newPhoneNumber = $this->createMock(LibphonenumberPhoneNumber::class);
        $phoneNumber = $this->createMock(PhoneNumber::class);
        $validationCode = $this->createMock(ValidationCode::class);

        $phoneNumber->expects($this->once())
            ->method('setTemPhoneNumber')
            ->with($newPhoneNumber);
        $phoneNumber->expects($this->exactly(2))
            ->method('getSMSCode')
            ->willReturn($validationCode);
        $phoneNumber->expects($this->exactly(1))
            ->method('getMailCode')
            ->willReturn($validationCode);

        $profile = $this->mockProfile();
        $profile->method('getPhoneNumber')->willReturn($phoneNumber);
        $phoneNumber->method('getPhoneNumber')->willReturn($newPhoneNumber);

        $user = $this->createMock(User::class);

        $phoneUtilsMock = $this->createMock(PhoneNumberUtil::class);

        $phoneUtilsMock
            ->method('format')
            ->with($newPhoneNumber)
            ->willReturn("+2222222222");

        $emMock = $this->mockEntityManager($profile, $user, false);
        $emMock->expects($this->once())
            ->method('persist')
            ->with($profile);
        $emMock->expects($this->once())
            ->method('flush');

        $manager = new ProfileManager(
            $emMock,
            $this->mockUserRepository($user),
            $phoneUtilsMock,
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $manager->changePhone($profile, $newPhoneNumber);
    }

    public function testUpdateProfile(): void
    {
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);

        $emMock = $this->mockEntityManager($profile, $user, false);
        $emMock->expects($this->once())
            ->method('persist')
            ->with($profile);
        $emMock->expects($this->once())
            ->method('flush');

        $manager = new ProfileManager(
            $emMock,
            $this->mockUserRepository($user),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $manager->updateProfile($profile);
    }

    public function testVerifyPhone(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('removeRole');
        $user->expects($this->once())->method('addRole');

        $profile = $this->createMock(Profile::class);
        $phoneNumber = $this->createMock(PhoneNumber::class);

        $phoneNumber->method('setPhoneNumber')->willReturn($phoneNumber);
        $phoneNumber->method('setTemPhoneNumber')->willReturn($phoneNumber);
        $phoneNumber->method('setVerified')->willReturn($phoneNumber);
        $phoneNumber->method('setSMSCode')->willReturn($phoneNumber);
        $phoneNumber->method('setMailCode')->willReturn($phoneNumber);
        $phoneNumber->method('setEditDate')->willReturn($phoneNumber);
        $phoneNumber->method('setEditAttempts')->willReturn($phoneNumber);

        $profile->method('getPhoneNumber')->willReturn($phoneNumber);
        $profile->method('getUser')->willReturn($user);

        $emMock = $this->mockEntityManager($profile, $user, false);

        $manager = new ProfileManager(
            $emMock,
            $this->mockUserRepository($user),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $manager->verifyPhone($profile, $this->createMock(LibphonenumberPhoneNumber::class));
    }

    public function testHandlePhoneNumberFailedAttempt(): void
    {
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);
        $phoneNumber = $this->createMock(PhoneNumber::class);

        $phoneNumber->expects($this->once())->method('incrementFailedAttempts');
        $profile->method('getPhoneNumber')->willReturn($phoneNumber);

        $emMock = $this->mockEntityManager($profile, $user, false);

        $manager = new ProfileManager(
            $emMock,
            $this->mockUserRepository($user),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $manager->handlePhoneNumberFailedAttempt($profile);
    }

    public function testUnverifyPhoneNumber(): void
    {
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);
        $phoneNumber = $this->createMock(PhoneNumber::class);

        $phoneNumber->expects($this->once())->method('setVerified')->with(false);
        $profile->method('getPhoneNumber')->willReturn($phoneNumber);

        $emMock = $this->mockEntityManager($profile, $user, false);

        $manager = new ProfileManager(
            $emMock,
            $this->mockUserRepository($user),
            $this->createMock(PhoneNumberUtil::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(PhoneNumberConfig::class),
        );

        $manager->unverifyPhoneNumber($profile);
    }


    /** @return Profile|MockObject */
    private function mockProfile(?string $firstName = null, ?string $lastName = null): Profile
    {
        $profile = $this->createMock(Profile::class);

        $profile->method('getFirstName')->willReturn($firstName ?? 'foo');
        $profile->method('getLastName')->willReturn($lastName ?? 'bar');

        return $profile;
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManager(
        ?Profile $profile,
        ?User $user = null,
        bool $hasMethodProfile = true
    ): EntityManagerInterface {
        $em = $this->createMock(EntityManagerInterface::class);

        $em
            ->method('getRepository')
            ->willReturnCallback(function (string $class) use ($profile, $user, $hasMethodProfile) {
                switch ($class) {
                    case Profile::class:
                        return $this->mockProfileRepository($profile, $hasMethodProfile);
                    case User::class:
                        return $this->mockUserRepository($user);
                }

                return $this->createMock(ServiceEntityRepositoryInterface::class);
            });

        return $em;
    }

    /** @return ProfileRepository|MockObject */
    private function mockProfileRepository(?Profile $profile, bool $hasMethods): ProfileRepository
    {
        $repo = $this->createMock(ProfileRepository::class);

        if ($hasMethods) {
            $repo
                ->expects($this->at(0))
                ->method('getProfileByNickname')
                ->willReturn($profile);
        }

        $repo->method('getProfileByUser')->willReturn($profile);

        return $repo;
    }

    /** @return UserRepository|MockObject */
    private function mockUserRepository(?User $user): UserRepository
    {
        $repo = $this->createMock(UserRepository::class);

        $repo->method('findByHash')
            ->willReturn($user);

        $repo->method('findByEmail')
            ->willReturn($user);

        return $repo;
    }
}
