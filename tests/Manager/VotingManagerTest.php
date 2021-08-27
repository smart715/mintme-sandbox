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
        $slug = 'some-random-slug';

        $tradable = $this->mockTradable([
            $this->mockVoting($slug),
        ]);

        $vm = new VotingManager($this->mockRepo());

        $this->assertEquals($vm->getBySlugForTradable($slug, $tradable)->getSlug(), $slug);
    }

    public function testGetByIdForTradableNotExist(): void
    {
        $tradable = $this->mockTradable([
            $this->mockVoting('another-ramdom-slug'),
        ]);

        $vm = new VotingManager($this->mockRepo());

        $this->assertEquals($vm->getBySlugForTradable('i-am-not-the-same', $tradable), null);
    }

    private function mockVoting(string $slug): Voting
    {
        $voting = $this->createMock(Voting::class);
        $voting->method('getSlug')->willReturn($slug);

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
