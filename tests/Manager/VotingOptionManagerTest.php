<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Voting\Option;
use App\Entity\Voting\Voting;
use App\Manager\VotingOptionManager;
use App\Repository\VotingOptionRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VotingOptionManagerTest extends TestCase
{
    public function testGetById(): void
    {
        $optionId = 1;
        $option = $this->mockOption($optionId);

        $votingOptionRepository = $this->mockVotingOptionRepository();
        $votingOptionRepository
            ->expects($this->exactly(2))
            ->method('find')
            ->with($optionId)
            ->willReturnOnConsecutiveCalls($option, null);

        $votingOptionManager = new VotingOptionManager($votingOptionRepository);

        $this->assertEquals($option, $votingOptionManager->getById($optionId));
        $this->assertNull($votingOptionManager->getById($optionId));
    }

    public function testGetByIdFromVotingExist(): void
    {
        $voting = $this->mockVoting([
            $this->mockOption(1),
        ]);

        $votingOptionManager = new VotingOptionManager($this->mockVotingOptionRepository());

        $this->assertEquals(1, $votingOptionManager->getByIdFromVoting(1, $voting)->getId());
    }

    public function testGetByIdFromVotingNotExist(): void
    {
        $voting = $this->mockVoting([
            $this->mockOption(2),
        ]);

        $votingOptionManager = new VotingOptionManager($this->mockVotingOptionRepository());

        $this->assertNull($votingOptionManager->getByIdFromVoting(1, $voting));
    }

    /** @return MockObject|Voting */
    private function mockVoting(array $options): Voting
    {
        $voting = $this->createMock(Voting::class);
        $voting->method('getOptions')->willReturn($options);

        return $voting;
    }

    /** @return MockObject|Option */
    private function mockOption(int $id): Option
    {
        $option = $this->createMock(Option::class);
        $option->method('getId')->willReturn($id);

        return $option;
    }

    /** @return MockObject|VotingOptionRepository */
    private function mockVotingOptionRepository(): VotingOptionRepository
    {
        return $this->createMock(VotingOptionRepository::class);
    }
}
