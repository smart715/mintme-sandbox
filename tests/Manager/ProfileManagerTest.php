<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Manager\ProfileManager;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    /** @return Profile|MockObject */
    private function mockProfile(?string $firstName = null, ?string $lastName = null): Profile
    {
        $profile = $this->createMock(Profile::class);

        $profile->method('getFirstName')->willReturn($firstName);
        $profile->method('getLastName')->willReturn($lastName);

        return $profile;
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManager(?Profile $profile): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $em
            ->method('getRepository')
            ->willReturn($this->mockProfileRepository($profile));

        return $em;
    }

    /** @return ProfileRepository|MockObject */
    private function mockProfileRepository(?Profile $profile): ProfileRepository
    {
        $repo = $this->createMock(ProfileRepository::class);

        $repo
            ->expects($this->at(0))
            ->method('getProfileByPageUrl')
            ->willReturn($profile);

        return $repo;
    }
}
