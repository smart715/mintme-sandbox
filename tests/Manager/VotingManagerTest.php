<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\TradebleInterface;
use App\Entity\Voting\Voting;
use App\Manager\VotingManager;
use App\Repository\VotingRepository;
use PHPUnit\Framework\TestCase;

class VotingManagerTest extends TestCase
{
    public function testGetByIdForTradableExist(): void
    {
        $tradable = $this->mockTradable([
            $this->mockVoting(1),
        ]);
        $vm = new VotingManager($this->mockRepo());

        $this->assertEquals($vm->getByIdForTradable(1, $tradable)->getId(), 1);
    }

    public function testGetByIdForTradableNotExist(): void
    {
        $tradable = $this->mockTradable([
            $this->mockVoting(2),
        ]);
        $vm = new VotingManager($this->mockRepo());

        $this->assertEquals($vm->getByIdForTradable(1, $tradable), null);
    }

    private function mockVoting(int $id): Voting
    {
        $voting = $this->createMock(Voting::class);
        $voting->method('getId')->willReturn($id);

        return $voting;
    }

    private function mockTradable(array $votings): TradebleInterface
    {
        $tradable = $this->createMock(TradebleInterface::class);
        $tradable->method('getVotings')->willReturn($votings);

        return $tradable;
    }

    private function mockRepo(): VotingRepository
    {
        return $this->createMock(VotingRepository::class);
    }
}
