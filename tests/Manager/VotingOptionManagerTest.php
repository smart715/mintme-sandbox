<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Voting\Option;
use App\Entity\Voting\Voting;
use App\Manager\VotingOptionManager;
use App\Repository\VotingOptionRepository;
use PHPUnit\Framework\TestCase;

class VotingOptionManagerTest extends TestCase
{
    public function testGetByIdFromVotingExist(): void
    {
        $voting = $this->mockVoting([
            $this->mockOption(1),
        ]);

        $vm = new VotingOptionManager($this->mockRepo());

        $this->assertEquals($vm->getByIdFromVoting(1, $voting)->getId(), 1);
    }

    public function testGetByIdFromVotingNotExist(): void
    {
        $voting = $this->mockVoting([
            $this->mockOption(2),
        ]);

        $vm = new VotingOptionManager($this->mockRepo());

        $this->assertEquals($vm->getByIdFromVoting(1, $voting), null);
    }

    private function mockVoting(array $options): Voting
    {
        $voting = $this->createMock(Voting::class);
        $voting->method('getOptions')->willReturn($options);

        return $voting;
    }

    private function mockOption(int $id): Option
    {
        $voting = $this->createMock(Option::class);
        $voting->method('getId')->willReturn($id);

        return $voting;
    }

    private function mockRepo(): VotingOptionRepository
    {
        return $this->createMock(VotingOptionRepository::class);
    }
}
