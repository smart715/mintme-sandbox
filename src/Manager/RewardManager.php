<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Rewards\RewardVolunteer;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\BalanceTransactionBonusType;
use App\Exchange\Balance\Factory\UpdateBalanceView;
use App\Logger\UserActionLogger;
use App\Repository\RewardParticipantRepository;
use App\Repository\RewardRepository;
use App\Repository\RewardVolunteerRepository;
use App\Utils\Converter\SlugConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;

class RewardManager implements RewardManagerInterface
{

    public const REWARD_UPDATE_ID = 'reward_update';
    public const REWARD_CREATE_ID = 'reward_create';
    public const REWARD_DELETE_ID = 'reward_delete';
    public const REWARD_PARTICIPATE_ID = 'reward_participate';
    public const REWARD_REFUND_ID = 'reward_refund';

    private RewardRepository $repository;
    private EntityManagerInterface $entityManager;
    private BalanceHandlerInterface $balanceHandler;
    private RewardVolunteerRepository $volunteerRepository;
    private UserActionLogger $userActionLogger;
    private MoneyWrapperInterface $moneyWrapper;
    private RewardParticipantRepository $participantRepository;
    private SlugConverterInterface $slugConverter;

    public function __construct(
        RewardRepository $rewardRepository,
        RewardVolunteerRepository $volunteerRepository,
        RewardParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager,
        BalanceHandlerInterface $balanceHandler,
        UserActionLogger $userActionLogger,
        MoneyWrapperInterface $moneyWrapper,
        SlugConverterInterface $slugConverter
    ) {
        $this->repository = $rewardRepository;
        $this->entityManager = $entityManager;
        $this->balanceHandler = $balanceHandler;
        $this->volunteerRepository = $volunteerRepository;
        $this->participantRepository = $participantRepository;
        $this->userActionLogger = $userActionLogger;
        $this->moneyWrapper = $moneyWrapper;
        $this->slugConverter = $slugConverter;
    }

    /**
     * Returns rewards grouped by type
     */
    public function getUnfinishedRewardsByToken(Token $token): array
    {
        $rewards = $this->repository->getActiveRewards($token);

        $sorted = [
            Reward::TYPE_REWARD => [],
            Reward::TYPE_BOUNTY => [],
        ];

        $unfinishedRewards = array_filter(
            $rewards,
            fn(Reward $reward) => !$reward->isFinishedReward()
        );

        /** @var Reward $reward */
        foreach ($unfinishedRewards as $reward) {
            if (Reward::TYPE_REWARD === $reward->getType()) {
                array_push($sorted[Reward::TYPE_REWARD], $reward);
            }

            if (Reward::TYPE_BOUNTY === $reward->getType()) {
                array_push($sorted[Reward::TYPE_BOUNTY], $reward);
            }
        }

        return $sorted;
    }

    public function getBySlug(string $slug, bool $onlyActive = true): ?Reward
    {
        $params = ['slug' => $slug];

        if ($onlyActive) {
            $params['status'] = Reward::STATUS_ACTIVE;
        }

        return $this->repository->findOneBy($params);
    }

    public function createReward(Reward $reward): void
    {
        $token = $reward->getToken();

        if ($reward->isBountyType()) {
            $balance = $this->balanceHandler->exchangeBalance($token->getOwner(), $token);
            $reward->setFrozenAmount(
                $reward->getPrice()->multiply($reward->getQuantity())
            );

            if ($reward->getFrozenAmount()->greaterThan($balance)) {
                throw new NotEnoughAmountException();
            }

            try {
                $this->balanceHandler->beginTransaction();
                $this->balanceHandler->update(
                    $token->getOwner(),
                    $token,
                    $reward->getFrozenAmount()->negative(),
                    self::REWARD_CREATE_ID
                );
            } catch (\Throwable $e) {
                $this->balanceHandler->rollback();

                throw $e;
            }
        }

        $title = trim($reward->getTitle());
        $slug = $this->slugConverter->convert($title, $this->repository);

        $reward->setSlug($slug);

        $this->userActionLogger->info('[Rewards] Reward created.', [
            'Type: '.$reward->getType(),
            '. Title: '.$title,
            '. Token name: '.$reward->getToken()->getName(),
            '. Price: '.$this->moneyWrapper->format($reward->getPrice()),
            '. Quantity: '.$reward->getQuantity(),
        ]);

        $this->entityManager->persist($reward);
        $this->entityManager->flush();
    }

    public function deleteReward(Reward $reward): void
    {
        if ($reward->isBountyType()) {
            $token = $reward->getToken();

            try {
                $this->balanceHandler->beginTransaction();
                $this->balanceHandler->update(
                    $token->getOwner(),
                    $token,
                    $reward->getFrozenAmount(),
                    self::REWARD_DELETE_ID
                );
            } catch (\Throwable $e) {
                $this->balanceHandler->rollback();

                throw $e;
            }
        }

        $reward->setStatus(Reward::STATUS_DELETED);
        $this->entityManager->persist($reward);
        $this->entityManager->flush();
        $this->userActionLogger->info('[Rewards] Reward deleted.', [
            'Type: '.$reward->getType(),
            '. Title: '.$reward->getTitle(),
            '. Token name: '.$reward->getToken()->getName(),
            '. Price: '.$this->moneyWrapper->format($reward->getPrice()),
            '. Quantity: '.$reward->getQuantity(),
            '. Participants count: '.$reward->getParticipants()->count(),
            '. Volunteers count: '.$reward->getVolunteers()->count(),
        ]);
    }

    public function saveReward(Reward $reward, Money $oldPrice, int $oldQuantity): void
    {
        if ($reward->isBountyType() && $reward->getQuantity() !== $oldQuantity) {
            $token = $reward->getToken();
            $balance = $this->balanceHandler->exchangeBalance(
                $token->getOwner(),
                $token
            );
            $frozenAmount = $reward->getFrozenAmount();

            $amountToFreeze = $reward->getPrice()->multiply(
                $reward->getQuantity() - $reward->getParticipants()->count()
            );

            // User increased quantity. Withdraw additional amount because total frozen amount was increased
            if ($amountToFreeze->greaterThan($frozenAmount)) {
                $amountToWithdraw = $amountToFreeze->subtract($frozenAmount);

                if ($amountToWithdraw->greaterThan($balance)) {
                    throw new NotEnoughAmountException();
                }

                try {
                    $this->balanceHandler->beginTransaction();
                    $this->balanceHandler->update(
                        $token->getOwner(),
                        $token,
                        $amountToWithdraw->negative(),
                        self::REWARD_UPDATE_ID
                    );
                } catch (\Throwable $e) {
                    $this->balanceHandler->rollback();

                    throw $e;
                }

                $reward->setFrozenAmount($frozenAmount->add($amountToWithdraw));
            } elseif ($amountToFreeze->lessThan($frozenAmount)) {
                // User decreased quantity. Deposit diff between old frozen amount and actual
                $amountToDeposit = $frozenAmount->subtract($amountToFreeze);

                try {
                    $this->balanceHandler->beginTransaction();
                    $this->balanceHandler->update(
                        $token->getOwner(),
                        $token,
                        $amountToDeposit,
                        self::REWARD_UPDATE_ID
                    );
                } catch (\Throwable $e) {
                    $this->balanceHandler->rollback();

                    throw $e;
                }

                $reward->setFrozenAmount($amountToFreeze);
            }
        }

        $this->userActionLogger->info('[Rewards] Reward edited.', [
            'Type: '.$reward->getType(),
            '. Title: '.$reward->getTitle(),
            '. Token name: '.$reward->getToken()->getName(),
            '. New price: '.$this->moneyWrapper->format($reward->getPrice()),
            '. Old price: '.$this->moneyWrapper->format($oldPrice),
            '. Old quantity: '.$oldQuantity,
            '. New quantity: '.$reward->getQuantity(),
            '. Participants count: '.$reward->getParticipants()->count(),
            '. Volunteers count: '.$reward->getVolunteers()->count(),
        ]);

        $this->entityManager->persist($reward);
        $this->entityManager->flush();
    }

    public function addMember(RewardMemberInterface $member): Reward
    {
        $withdrawResult = $this->payRewardIfRequired($member);

        if (!$member->getReward()->isBountyType() && $member instanceof RewardParticipant) {
            $member->setStatus(RewardParticipant::COMPLETED_STATUS);

            if ($withdrawResult) {
                $member
                    ->setPrice($withdrawResult->getChange())
                    ->setBonusPrice($withdrawResult->getBonusChange());
            }
        }

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $this->userActionLogger->info('[Rewards] New member.', [
            'Type: '.$member->getReward()->getType(),
            '. Title: '.$member->getReward()->getType(),
            '. Token name: '.$member->getReward()->getToken()->getName(),
            '. Member: '.$member->getUser()->getEmail(),
        ]);

        return $member->getReward()->addMember($member);
    }

    public function removeParticipant(RewardMemberInterface $participant): Reward
    {
        $reward = $participant->getReward();
        $this->entityManager->remove($participant);
        $this->entityManager->flush();

        $this->userActionLogger->info('[Rewards] Remove bounty participant.', [
            'Type: '.$reward->getType(),
            '. Title: '.$reward->getTitle(),
            '. Token name: '.$reward->getToken()->getName(),
            '. Member: '.$participant->getUser()->getEmail(),
        ]);

        return $reward;
    }

    public function acceptMember(RewardVolunteer $member): Reward
    {
        $reward = $member->getReward();

        $participant = (new RewardParticipant())
            ->setUser($member->getUser())
            ->setReward($reward)
            ->setNote($member->getNote())
            ->setPrice($reward->getPrice())
            ->setStatus(RewardParticipant::NOT_COMPLETED_STATUS);

        $reward
            ->removeVolunteer($member)
            ->addParticipant($participant);

        $this->entityManager->persist($reward);

        $this->userActionLogger->info('[Rewards] Volunteer accepted', [
            'Type: '.$reward->getType(),
            '. Title: '.$reward->getTitle(),
            '. Token name: '.$reward->getToken()->getName(),
            '. Member: '.$member->getUser()->getEmail(),
        ]);

        // remove all volunteers once given bounty reaches max slots
        if ($reward->isQuantityReached()) {
            $volunteers = $reward->getVolunteers();

            foreach ($volunteers as $volunteer) {
                $this->entityManager->remove($volunteer);
            }
        }

        $this->entityManager->flush();

        return $reward;
    }

    public function completeMember(RewardParticipant $participant): Reward
    {
        $reward = $participant->getReward();

        if (!$participant->isCompleted()) {
            $participant->setStatus(RewardParticipant::COMPLETED_STATUS);
            $this->entityManager->persist($participant);

            $reward->setFrozenAmount($reward->getFrozenAmount()->subtract($reward->getPrice()));

            $this->entityManager->persist($reward);
            $this->payRewardIfRequired($participant);
        }

        $this->userActionLogger->info('[Rewards] Participant completed .', [
            'Type: '.$reward->getType(),
            '. Title: '.$reward->getTitle(),
            '. Token name: '.$reward->getToken()->getName(),
            '. Member: '.$participant->getUser()->getEmail(),
        ]);

        $this->entityManager->flush();

        return $reward;
    }

    public function refundReward(Reward $reward, RewardParticipant $participant): Reward
    {
        $participant->setStatus(RewardParticipant::REFUNDED_STATUS);

        $user = $participant->getUser();
        $token = $reward->getToken();
        $owner = $token->getOwner();
        $price = $participant->getPrice();
        $bonusPrice = $participant->getBonusPrice();

        if (!$price->isZero()) {
            $this->balanceHandler->update($user, $token, $price, self::REWARD_REFUND_ID);
        }

        if (!$bonusPrice->isZero()) {
            $this->balanceHandler->depositBonus($user, $token, $bonusPrice, self::REWARD_REFUND_ID);
        }

        $this->balanceHandler->update(
            $owner,
            $token,
            $participant->getFullPrice()->negative(),
            self::REWARD_REFUND_ID
        );

        $this->entityManager->persist($participant);
        $this->entityManager->flush();

        return $reward;
    }

    public function findMember(User $user, Reward $reward): ?RewardMemberInterface
    {
        return $this->volunteerRepository->findVolunteerByUserAndReward($user, $reward)
            ?? $this->participantRepository->findParticipantByUserAndReward($user, $reward);
    }

    public function findMemberById(int $id, Reward $reward): ?RewardMemberInterface
    {
        return $this->volunteerRepository->findVolunteerById($id)
            ?? $this->participantRepository->findParticipantById($id);
    }

    public function rejectVolunteer(RewardVolunteer $rewardVolunteer): Reward
    {
        $reward = $rewardVolunteer->getReward();
        $this->entityManager->remove($rewardVolunteer);
        $this->entityManager->flush();

        $this->userActionLogger->info('[Rewards] Volunteer rejected.', [
            'Type: '.$reward->getType(),
            '. Title: '.$reward->getTitle(),
            '. Volunteer: '.$rewardVolunteer->getUser()->getEmail(),
        ]);

        return $reward;
    }

    public function setParticipantStatus(RewardParticipant $participant, string $status): RewardParticipant
    {
        $participant->setStatus($status);
        $this->entityManager->persist($participant);
        $this->entityManager->flush();

        return $participant;
    }

    private function payRewardIfRequired(RewardMemberInterface $member): ?UpdateBalanceView
    {
        $withdrawResult = null;

        // If confirmation required, we shouldn't pay to user, because token owner should accept volunteer at first.
        if ($member->isConfirmationRequired()) {
            return $withdrawResult;
        }

        $user = $member->getUser();
        $reward = $member->getReward();
        $token = $reward->getToken();

        if (!$reward->isBountyType() &&
            $this->balanceHandler->exchangeBalance(
                $user,
                $token,
                true
            )->lessThan($reward->getPrice())
        ) {
            throw new NotEnoughAmountException();
        }

        $depositTo = $reward->isBountyType()
            ? $member->getUser()
            : $token->getOwner();

        try {
            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->update(
                $depositTo,
                $token,
                $reward->getPrice(),
                BalanceTransactionBonusType::REWARD_PARTICIPATE
            );

            // If reward type then we should withdraw from user and deposit to token owner.
            // If bounty then only deposit to user, because from token owner all funds already withdrawn (frozen)
            if (!$reward->isBountyType()) {
                $withdrawResult = $this->balanceHandler->withdrawBonus(
                    $user,
                    $token,
                    $reward->getPrice(),
                    BalanceTransactionBonusType::REWARD_PARTICIPATE
                );
            }
        } catch (\Throwable $e) {
            $this->balanceHandler->rollback();

            throw $e;
        }

        return $withdrawResult;
    }
}
