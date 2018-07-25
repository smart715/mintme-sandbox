<?php

namespace App\Tests\Fetcher;

use App\Entity\Profile;
use App\Entity\User;
use App\Fetcher\ProfileFetcher;
use App\Manager\ProfileManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProfileFetcherTest extends TestCase
{
    /** @dataProvider profileProvider
     * @param mixed $expected
    */
    public function testFetchProfile($expected): void
    {
        $user = $this->createMock(User::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $profileManager = $this->createMock(ProfileManagerInterface::class);
        $profileManager->method('getProfile')->with($user)->willReturn($expected);

        $fetcher = new ProfileFetcher($profileManager, $tokenStorage);
        $this->assertEquals($expected, $fetcher->fetchProfile());
    }

    public function profileProvider(): array
    {
        return [
            [$this->createMock(Profile::class)],
            [null],
        ];
    }

    public function testFetchProfileThrowsExceptionIfTokenStorageDoesNotContainToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $profileManager = $this->createMock(ProfileManagerInterface::class);
        $this->expectException(RuntimeException::class);
        (new ProfileFetcher($profileManager, $tokenStorage))->fetchProfile();
    }
}
