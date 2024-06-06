<?php declare(strict_types = 1);

namespace App\Manager\PromotionHistory;

use App\Config\LimitHistoryConfig;
use App\Entity\PromotionHistoryInterface;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\PromotionHistoryRepositoryInterface;

class PromotionHistoryTableManager implements PromotionHistoryTableManagerInterface
{
    public const PER_PAGE_DEFAULT = 20;
    private PromotionHistoryRepositoryInterface $promotionHistoryRepository;

    private int $page;

    private int $perPage;

    private int $pointer;

    private User $user;

    /** @var PromotionHistoryInterface[] */
    private array $promotionHistory;
    private LimitHistoryConfig $limitHistoryConfig;

    public function __construct(
        PromotionHistoryRepositoryInterface $promotionHistoryRepository,
        User $user,
        LimitHistoryConfig $limitHistoryConfig,
        int $perPage = self::PER_PAGE_DEFAULT
    ) {
        $this->limitHistoryConfig = $limitHistoryConfig;
        $this->promotionHistoryRepository = $promotionHistoryRepository;
        $this->user = $user;
        $this->perPage = $perPage;
        $this->page = 0;
        $this->pointer = 0;

        $this->nextPage();
    }

    public function getCurrentElement(): ?PromotionHistoryInterface
    {
        return $this->promotionHistory[$this->pointer] ?? null;
    }

    public function nextElement(): void
    {
        $this->movePointer();

        if (!isset($this->promotionHistory[$this->pointer])) {
            $this->nextPage();
        }
    }

    private function fetchNextPage(): void
    {
        $this->resetPointer();

        $offset = ($this->page - 1) * $this->perPage;
        $this->promotionHistory = $this->promotionHistoryRepository->getPromotionHistoryByUserAndToken(
            $this->user,
            $offset,
            $this->perPage,
            $this->limitHistoryConfig->getFromDate()
        );
    }

    private function nextPage(): void
    {
        $this->page++;
        $this->fetchNextPage();
    }

    private function movePointer(): void
    {
        $this->pointer++;
    }

    private function resetPointer(): void
    {
        $this->pointer = 0;
    }
}
