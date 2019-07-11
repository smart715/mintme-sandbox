<?php declare(strict_types = 1);

namespace App\Tests\Utils\Fetcher;

use App\Entity\Profile;
use App\Manager\ProfileManagerInterface;
use App\Utils\Fetcher\ProfileFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProfileFetcherTest extends TestCase
{
    /** @var Profile|MockObject $profile */
    private $profile;

    public function setUp(): void
    {
        $this->profile = $this->createMock(Profile::class);
        parent::setUp();
    }
    public function testProfileFetcherThrowExceptionIfNotAuthenticated(): void
    {
        $profileFetcher = new ProfileFetcher($this->mockProfileManager($this->profile), $this->mockTokenStorage(false));

        $this->expectException(RuntimeException::class);

        $profileFetcher->fetchProfile();
    }

    public function testFetchProfileReturnProfileIfAuthenticated(): void
    {
        $profileFetcher = new ProfileFetcher($this->mockProfileManager($this->profile), $this->mockTokenStorage(true));

        $this->assertEquals($profileFetcher->fetchProfile(), $this->profile);
    }

    /**
     * @var Profile|MockObject $profile
     * @return MockObject|ProfileManagerInterface
     */
    private function mockProfileManager(Profile $profile): ProfileManagerInterface
    {

        $pm = $this->createMock(ProfileManagerInterface::class);
        $pm->method('getProfile')->willReturn($profile);

        return $pm;
    }

    /**
     * @var bool $authenticated
     * @return MockObject|TokenStorageInterface
     */
    private function mockTokenStorage(bool $authenticated): TokenStorageInterface
    {
        $token = $authenticated
            ? $this->createMock(TokenInterface::class)
            : null;

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return $storage;
    }
}
