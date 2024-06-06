<?php declare(strict_types = 1);

namespace App\Tests\Manager\PromotionHistory;

use App\Config\LimitHistoryConfig;
use App\Entity\PromotionHistory;
use App\Entity\User;
use App\Manager\PromotionHistory\PromotionHistoryManager;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\CommentTipRepository;
use App\Repository\PostUserShareRewardRepository;
use App\Repository\RewardParticipantRepository;
use App\Repository\TokenCryptoRepository;
use App\Repository\TokenDeployRepository;
use App\Repository\TokenPromotionRepository;
use App\Repository\TokenReleaseAddressHistoryRepository;
use App\Repository\TokenSignupHistoryRepository;
use PHPUnit\Framework\TestCase;

class PromotionHistoryManagerTest extends TestCase
{
    private const EXPECTED_HISTORY = 'expected history';

    private int $testId = 0; // phpcs:ignore

    public function testGetPromotionHistoryWithZeroOffsetAndBigDB(): void
    {
        $user = $this->createMock(User::class);
        $generatedDB = $this->generatePromotionHistoryDB();

        $phm = $this->setUpPromotionHistoryManager($generatedDB);
        $this->assertEquals(
            $generatedDB[self::EXPECTED_HISTORY],
            $phm->getPromotionHistory($user, 0, 10)
        );
    }

    public function testGetPromotionHistoryWithNotZeroOffset(): void
    {
        $user = $this->createMock(User::class);

        $rewardParticipant = $this->createMock(PromotionHistory::class);
        $rewardParticipant->testId = 1; /** @phpstan-ignore-line */
        $rewardParticipant
            ->method('getCreatedAt')
            ->willReturn(10000);

        $airdropParticipant = $this->createMock(PromotionHistory::class);
        $airdropParticipant->testId = 2; /** @phpstan-ignore-line */
        $airdropParticipant
            ->method('getCreatedAt')
            ->willReturn(1);

        $db = [
            RewardParticipantRepository::class => [$rewardParticipant],
            AirdropParticipantRepository::class => [$airdropParticipant],
        ];


        $phm = $this->setUpPromotionHistoryManager($db);
        $this->assertEquals(
            [$airdropParticipant],
            $phm->getPromotionHistory($user, 1, 10)
        );
    }

    public function testGetPromotionHistoryWithSmallLimit(): void
    {
        $user = $this->createMock(User::class);

        $rewardParticipant = $this->createMock(PromotionHistory::class);
        $rewardParticipant->testId = 1; /** @phpstan-ignore-line */
        $rewardParticipant
            ->method('getCreatedAt')
            ->willReturn(10000);

        $airdropParticipant = $this->createMock(PromotionHistory::class);
        $airdropParticipant->testId = 2; /** @phpstan-ignore-line */
        $airdropParticipant
            ->method('getCreatedAt')
            ->willReturn(1);

        $db = [
            RewardParticipantRepository::class => [$rewardParticipant],
            AirdropParticipantRepository::class => [$airdropParticipant],
        ];

        $phm = $this->setUpPromotionHistoryManager($db);
        $this->assertEquals(
            [$rewardParticipant],
            $phm->getPromotionHistory($user, 0, 1)
        );
    }

    private function generatePromotionHistoryDB(): array
    {
        $rewardParticipants = $this->createPromotionHistoryMocks([5, 3]);
        $airdropParticipants = $this->createPromotionHistoryMocks([1, 0, 0]);
        $postUserShareRewards = $this->createPromotionHistoryMocks([4, 0, 0]);
        $tokenSignUpHistory = $this->createPromotionHistoryMocks([6, 0, 0]);
        $commentTips = $this->createPromotionHistoryMocks([7, 0, 0]);
        $tokenDeployHistory = $this->createPromotionHistoryMocks([8, 0, 0]);
        $tokenReleaseAddressHistory = $this->createPromotionHistoryMocks([9, 0, 0]);
        $tokenCryptoHistory = $this->createPromotionHistoryMocks([10, 0, 0]);
        $tokenPromotionHistory = $this->createPromotionHistoryMocks([11, 0, 0]);
        $expectedPromotionHistory = [
            $tokenPromotionHistory[0],
            $tokenCryptoHistory[0],
            $tokenReleaseAddressHistory[0],
            $tokenDeployHistory[0],
            $commentTips[0],
            $tokenSignUpHistory[0],
            $rewardParticipants[0],
            $postUserShareRewards[0],
            $rewardParticipants[1],
            $airdropParticipants[0],
        ];

        return [
            RewardParticipantRepository::class => $rewardParticipants,
            AirdropParticipantRepository::class => $airdropParticipants,
            PostUserShareRewardRepository::class => $postUserShareRewards,
            CommentTipRepository::class => $commentTips,
            TokenSignupHistoryRepository::class => $tokenSignUpHistory,
            TokenDeployRepository::class => $tokenDeployHistory,
            TokenReleaseAddressHistoryRepository::class => $tokenReleaseAddressHistory,
            TokenCryptoRepository::class => $tokenCryptoHistory,
            TokenPromotionRepository::class => $tokenPromotionHistory,
            self::EXPECTED_HISTORY => $expectedPromotionHistory,
        ];
    }

    private function createPromotionHistoryMocks(array $creationDates): array
    {
        $response = [];

        foreach ($creationDates as $creationDate) {
            $mock = $this->createMock(PromotionHistory::class);
            $mock->testId = $this->testId; /** @phpstan-ignore-line */
            $mock->method('getCreatedAt')
                ->willReturn($creationDate);

            $response[] = $mock;

            $this->testId++;
        }

        return $response;
    }

    private function setUpPromotionHistoryManager(array $db): PromotionHistoryManager
    {
        return new PromotionHistoryManager(
            $this->mockRewardParticipantRepository($db[RewardParticipantRepository::class] ?? null),
            $this->mockAirdropParticipantRepository($db[AirdropParticipantRepository::class] ?? null),
            $this->mockPostUserShareRewardRepository($db[PostUserShareRewardRepository::class] ?? null),
            $this->mockCommentTipRepository($db[CommentTipRepository::class] ?? null),
            $this->mockTokenSignupHistoryRepository($db[TokenSignupHistoryRepository::class] ?? null),
            $this->mockTokenDeployRepository($db[TokenDeployRepository::class] ?? null),
            $this->mockTokenReleaseAddressHistoryRepository($db[TokenReleaseAddressHistoryRepository::class] ?? null),
            $this->mockTokenCryptoRepository($db[TokenCryptoRepository::class] ?? null),
            $this->mockTokenPromotionRepository($db[TokenPromotionRepository::class] ?? null),
            $this->limitHistoryConfigMock()
        );
    }

    private function mockRewardParticipantRepository(?array $repositoryResponse = null): RewardParticipantRepository
    {
        $repository = $this->createMock(RewardParticipantRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockAirdropParticipantRepository(?array $repositoryResponse = null): AirdropParticipantRepository
    {
        $repository = $this->createMock(AirdropParticipantRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockPostUserShareRewardRepository(?array $repositoryResponse = null): PostUserShareRewardRepository
    {
        $repository = $this->createMock(PostUserShareRewardRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockCommentTipRepository(?array $repositoryResponse = null): CommentTipRepository
    {
        $repository = $this->createMock(CommentTipRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockTokenSignupHistoryRepository(?array $repositoryResponse = null): TokenSignupHistoryRepository
    {
        $repository = $this->createMock(TokenSignupHistoryRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockTokenDeployRepository(?array $repositoryResponse = null): TokenDeployRepository
    {
        $repository = $this->createMock(TokenDeployRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockTokenReleaseAddressHistoryRepository(
        ?array $repositoryResponse = null
    ): TokenReleaseAddressHistoryRepository {
        $repository = $this->createMock(TokenReleaseAddressHistoryRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockTokenCryptoRepository(?array $repositoryResponse = null): TokenCryptoRepository
    {
        $repository = $this->createMock(TokenCryptoRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function mockTokenPromotionRepository(?array $repositoryResponse = null): TokenPromotionRepository
    {
        $repository = $this->createMock(TokenPromotionRepository::class);
        $repository
            ->method('getPromotionHistoryByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $repositoryResponse ?? [],
                []
            );

        return $repository;
    }

    private function limitHistoryConfigMock(): LimitHistoryConfig
    {
        return $this->createMock(LimitHistoryConfig::class);
    }
}
