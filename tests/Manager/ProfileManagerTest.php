<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Entity\User;
use App\Manager\ProfileManager;
use App\Repository\ProfileRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class ProfileManagerTest extends TestCase
{
    public function testGeneratePageUrl(): void
    {
        $profile = $this->mockProfile('foo', 'bar');
        $emptyProfile = $this->mockProfile();

        $manager = new ProfileManager($this->mockEntityManager(null));

        $this->assertEquals('foo.bar', $manager->generatePageUrl($profile));

        $manager = new ProfileManager($this->mockEntityManager($profile));

        $this->assertRegExp('/^foo\.baz\..+/', $manager->generatePageUrl($this->mockProfile('foo', 'baz')));
        $this->assertEquals('foo.bar', $manager->generatePageUrl($profile));
        $this->assertEquals('foo-bar.baz', $manager->generatePageUrl($this->mockProfile('foo bar', 'baz')));
        $this->assertEquals('foo-bar.baz', $manager->generatePageUrl($this->mockProfile('foo-bar', 'baz')));
        $this->expectException(\Throwable::class);

        $manager->generatePageUrl($emptyProfile);
    }

    public function testFindProfileByHash(): void
    {
        $profile = $this->mockProfile();
        $user = $this->createMock(User::class);

        $manager = new ProfileManager($this->mockEntityManager($profile, $user, false));
        $this->assertEquals($user, $manager->findProfileByHash('qwe'));
        $this->assertEquals(null, $manager->findProfileByHash(null));
        $this->assertEquals(null, $manager->findProfileByHash(''));
    }

    public function testGetProfile(): void
    {
        $profile = $this->mockProfile();
        $user = $this->createMock(User::class);

        $manager = new ProfileManager($this->mockEntityManager($profile, $user, false));

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

        $manager = new ProfileManager($this->mockEntityManager($profile, $user, false));

        $this->assertEquals($this->createMock(Profile::class), $manager->findByEmail('foo@bar.baz'));

        $manager = new ProfileManager($this->mockEntityManager($profile, null, false));

        $this->assertEquals(null, $manager->findByEmail('foo@bar.baz'));
    }

    /** @return Profile|MockObject */
    private function mockProfile(?string $firstName = null, ?string $lastName = null): Profile
    {
        $profile = $this->createMock(Profile::class);

        $profile->method('getFirstName')->willReturn($firstName);
        $profile->method('getLastName')->willReturn($lastName);

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
                ->method('getProfileByPageUrl')
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
