<?php

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Entity\Token;
use App\Fetcher\ProfileFetcherInterface;
use App\Manager\TokenManager;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;

class TokenManagerTest extends TestCase
{
    public function testCreateToken(): void
    {
        $profile = $this->createMock(Profile::class);
        $profileFetcher = $this->getProfileFetcherMock($profile);

        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $profileFetcher
        );
        $this->assertInstanceOf(Token::class, $tokenManager->createToken());
    }

    public function testFindByName(): void
    {
        $name = 'TOKEN';
        $token = $this->createMock(Token::class);
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository->method('findByName')->with($name)->willReturn($token);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($tokenRepository);

        $tokenManager = new TokenManager(
            $entityManager,
            $this->createMock(ProfileFetcherInterface::class)
        );
        $this->assertEquals($token, $tokenManager->findByName($name));
    }

    public function testGetOwnTokenWhenProfileCreated(): void
    {
        $profile = $this->createMock(Profile::class);
        $profileFetcher = $this->getProfileFetcherMock($profile);

        $token = $this->createMock(Token::class);
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository->method('findByProfile')->with($profile)->willReturn($token);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($tokenRepository);

        $tokenManager = new TokenManager($entityManager, $profileFetcher);
        $this->assertEquals($token, $tokenManager->getOwnToken());
    }

    public function testGetOwnTokenWhenProfileNotCreated(): void
    {
        $profileFetcher = $this->getProfileFetcherMock(null);

        $tokenManager = new TokenManager(
            $this->createMock(EntityManagerInterface::class),
            $profileFetcher
        );
        $this->assertEquals(null, $tokenManager->getOwnToken());
    }

    private function getProfileFetcherMock(?Profile $profile): ProfileFetcherInterface
    {
        $profileFetcher = $this->createMock(ProfileFetcherInterface::class);
        $profileFetcher->method('fetchProfile')->willReturn($profile);
        return $profileFetcher;
    }
}
