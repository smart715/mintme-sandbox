<?php

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Fetcher\ProfileFetcherInterface;
use App\Manager\TokenManager;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;

class TokenManagerTest extends TestCase
{
    public function testFindByName(): void
    {
        $name = 'TOKEN';
        $token = $this->createMock(Token::class);
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository->method('findByName')->with($name)->willReturn($token);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($tokenRepository);

        $tokenManager = new TokenManager($entityManager, $this->createMock(ProfileFetcherInterface::class));
        $this->assertEquals($token, $tokenManager->findByName($name));
    }

    public function testGetOwnTokenWhenProfileCreated(): void
    {
        $token = $this->createMock(Token::class);

        $profile = $this->createMock(Profile::class);
        $profile->method('getToken')->willReturn($token);

        $profileFetcher = $this->createMock(ProfileFetcherInterface::class);
        $profileFetcher->method('fetchProfile')->willReturn($profile);

        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $profileFetcher
        );
        $this->assertEquals($token, $tokenManager->getOwnToken());
    }

    public function testGetOwnTokenWhenProfileNotCreated(): void
    {
        $profileFetcher = $this->createMock(ProfileFetcherInterface::class);
        $profileFetcher->method('fetchProfile')->willReturn(null);

        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $profileFetcher
        );
        $this->assertEquals(null, $tokenManager->getOwnToken());
    }
}
