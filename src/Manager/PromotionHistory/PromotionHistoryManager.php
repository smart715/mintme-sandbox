<?php declare(strict_types = 1);

namespace App\Manager\PromotionHistory;

use App\Config\LimitHistoryConfig;
use App\Entity\PromotionHistoryInterface;
use App\Entity\User;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\CommentTipRepository;
use App\Repository\PostUserShareRewardRepository;
use App\Repository\PromotionHistoryRepositoryInterface;
use App\Repository\RewardParticipantRepository;
use App\Repository\TokenCryptoRepository;
use App\Repository\TokenDeployRepository;
use App\Repository\TokenPromotionRepository;
use App\Repository\TokenReleaseAddressHistoryRepository;
use App\Repository\TokenSignupHistoryRepository;

class PromotionHistoryManager implements PromotionHistoryManagerInterface
{
    /** @var PromotionHistoryRepositoryInterface[] */
    private array $repositories;

    /** @var PromotionHistoryTableManagerInterface[] */
    private array $promotionHistoryTables;
    private LimitHistoryConfig $limitHistoryConfig;

    public function __construct(
        RewardParticipantRepository $rewardParticipantRepository,
        AirdropParticipantRepository $airdropParticipantRepository,
        PostUserShareRewardRepository $postUserShareRewardRepository,
        CommentTipRepository $commentTipRepository,
        TokenSignupHistoryRepository $tokenSignUpHistoryRepository,
        TokenDeployRepository $tokenDeployRepository,
        TokenReleaseAddressHistoryRepository $tokenReleaseAddressHistoryRepository,
        TokenCryptoRepository $tokenCryptoRepository,
        TokenPromotionRepository $tokenPromotionRepository,
        LimitHistoryConfig $limitHistoryConfig
    ) {
        $this->repositories = [];
        $this->promotionHistoryTables = [];

        array_push(
            $this->repositories,
            $rewardParticipantRepository,
            $airdropParticipantRepository,
            $postUserShareRewardRepository,
            $commentTipRepository,
            $tokenSignUpHistoryRepository,
            $tokenDeployRepository,
            $tokenReleaseAddressHistoryRepository,
            $tokenCryptoRepository,
            $tokenPromotionRepository,
        );
        $this->limitHistoryConfig = $limitHistoryConfig;
    }

    private function setConfig(User $user): void
    {
        foreach ($this->repositories as $repository) {
            $this->promotionHistoryTables[] = new PromotionHistoryTableManager(
                $repository,
                $user,
                $this->limitHistoryConfig
            );
        }
    }

    public function getPromotionHistory(
        User $user,
        int $offset,
        int $limit
    ): array {
        $this->setConfig($user);

        return $this->constructPromotionHistory($offset, $limit);
    }

    /**
     * @return PromotionHistoryInterface[]
     */
    private function constructPromotionHistory(int $offset, int $limit): array
    {
        /** @var PromotionHistoryInterface[] */
        $promotionHistory = [];

        while (count($promotionHistory) < $limit) {
            $newestNoteTableKey = $this->getNewestNoteTableKey();

            if (null === $newestNoteTableKey) {
                break;
            }

            if (0 === $offset) {
                /** @var PromotionHistoryInterface */
                $newestNote = $this->promotionHistoryTables[$newestNoteTableKey]->getCurrentElement();
                
                $promotionHistory[] = $newestNote;
            } else {
                $offset--;
            }

            $this->promotionHistoryTables[$newestNoteTableKey]->nextElement();
        }

        return $promotionHistory;
    }

    private function getNewestNoteTableKey(): ?int
    {
        /** @var int|null */
        $newestNoteTableKey = null;

        /** @var PromotionHistoryInterface|null */
        $newestNote = null;

        foreach ($this->promotionHistoryTables as $key => $promotionHistoryTable) {
            $note = $promotionHistoryTable->getCurrentElement();

            if (null === $note) {
                unset($this->promotionHistoryTables[$key]);

                continue;
            }

            if (null === $newestNote || $newestNote->getCreatedAt() < $note->getCreatedAt()) {
                $newestNote = $note;
                
                /** @var integer $newestNoteTableKey */
                $newestNoteTableKey = $key;
            }
        }

        return $newestNoteTableKey;
    }
}
