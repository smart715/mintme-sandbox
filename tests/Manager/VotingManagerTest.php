<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\Voting\Voting;
use App\Manager\VotingManager;
use App\Repository\CryptoVotingRepository;
use App\Repository\TokenVotingRepository;
use App\Repository\VotingRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VotingManagerTest extends TestCase
{
    public function testGetRepository(): void
    {
        $votingRepository  = $this->mockVotingRepository();
        $votingManger = new VotingManager(
            $votingRepository,
            $this->mockTokenVotingRepository(),
            $this->mockCryptoVotingRepository()
        );

        $this->assertEquals($votingRepository, $votingManger->getRepository());
    }

    public function testGetById(): void
    {
        $votingId = 1;

        $voting = $this->mockVoting();

        $votingRepository = $this->mockVotingRepository();
        $votingRepository
            ->expects($this->once())
            ->method('find')
            ->with($votingId)
            ->willReturn($voting);

        $votingManger = new VotingManager(
            $votingRepository,
            $this->mockTokenVotingRepository(),
            $this->mockCryptoVotingRepository()
        );

        $this->assertEquals($voting, $votingManger->getById($votingId));
    }

    public function testGetBySlug(): void
    {
        $slug = 'some-random-slug';

        $voting = $this->mockVoting();

        $votingRepository = $this->mockVotingRepository();
        $votingRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['slug' => $slug])
            ->willReturn($voting);

        $votingManger = new VotingManager(
            $votingRepository,
            $this->mockTokenVotingRepository(),
            $this->mockCryptoVotingRepository()
        );

        $this->assertEquals($voting, $votingManger->getBySlug($slug));
    }

    public function testGetByOptionId(): void
    {
        $optionId = 1;

        $voting = $this->mockVoting();

        $votingRepository = $this->mockVotingRepository();
        $votingRepository
            ->expects($this->once())
            ->method('getByOptionId')
            ->with($optionId)
            ->willReturn($voting);

        $votingManger = new VotingManager(
            $votingRepository,
            $this->mockTokenVotingRepository(),
            $this->mockCryptoVotingRepository()
        );

        $this->assertEquals($voting, $votingManger->getByOptionId($optionId));
    }

    public function testGetByIdForTradableExist(): void
    {
        $slug = 'some-random-slug';

        $voting = $this->mockVoting();
        $voting
            ->method('getSlug')
            ->willReturn($slug);

        $tradable = $this->mockTradable([$voting]);

        $votingManger = new VotingManager(
            $this->mockVotingRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockCryptoVotingRepository()
        );

        $this->assertEquals($slug, $votingManger->getBySlugForTradable($slug, $tradable)->getSlug());
    }

    public function testGetByIdForTradableNotExist(): void
    {
        $voting = $this->mockVoting();
        $voting
            ->method('getSlug')
            ->willReturn('another-ramdom-slug');

        $tradable = $this->mockTradable([$voting]);

        $votingManger = new VotingManager(
            $this->mockVotingRepository(),
            $this->mockTokenVotingRepository(),
            $this->mockCryptoVotingRepository()
        );

        $this->assertNull($votingManger->getBySlugForTradable('i-am-not-the-same', $tradable));
    }

    public function testGetAllCreatedByUserAndTokenOwner(): void
    {
        $blockedUser = $this->mockUser();
        $user = $this->mockUser();
        $votingRepository = $this->mockVotingRepository();
        $votings = [$this->mockVoting()];

        $tokenVotingRepository = $this->mockTokenVotingRepository();
        $tokenVotingRepository
            ->expects($this->once())
            ->method('getVotingsByCreatorIdAndProfileId')
            ->with($user->getId(), $blockedUser->getProfile()->getId())
            ->willReturn($votings);

        $votingManger = new VotingManager(
            $votingRepository,
            $tokenVotingRepository,
            $this->mockCryptoVotingRepository()
        );

        $this->assertEquals($votings, $votingManger->getAllCreatedByUserAndTokenOwner($blockedUser, $user));
    }

    /** @return MockObject|Voting */
    private function mockVoting(): Voting
    {
        return $this->createMock(Voting::class);
    }

    private function mockTradable(array $votings): TradableInterface
    {
        $tradable = $this->createMock(TradableInterface::class);
        $tradable->method('getVotings')->willReturn($votings);

        return $tradable;
    }

    /** @return MockObject|VotingRepository */
    private function mockVotingRepository(): VotingRepository
    {
        return $this->createMock(VotingRepository::class);
    }

    private function mockUser(): User
    {
        $mock = $this->createMock(User::class);
        $mock->method('getId')->willReturn(1);
        $mock->method('getProfile')->willReturn($this->mockProfile(1));

        return $mock;
    }

    private function mockProfile(int $id): Profile
    {
        $mock = $this->createMock(Profile::class);
        $mock->method('getId')->willReturn($id);

        return $mock;
    }

    /** @return MockObject|TokenVotingRepository */
    private function mockTokenVotingRepository(): TokenVotingRepository
    {
        return $this->createMock(TokenVotingRepository::class);
    }

    /** @return MockObject|CryptoVotingRepository */
    private function mockCryptoVotingRepository(): CryptoVotingRepository
    {
        return $this->createMock(CryptoVotingRepository::class);
    }
}
