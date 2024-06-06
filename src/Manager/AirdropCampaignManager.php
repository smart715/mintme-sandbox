<?php declare(strict_types = 1);

namespace App\Manager;

use App\Activity\ActivityTypes;
use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\AirdropEvent;
use App\Events\TokenEvents;
use App\Events\UserAirdropEvent;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\BalanceTransactionBonusType;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\AirdropCampaign\AirdropRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AirdropCampaignManager implements AirdropCampaignManagerInterface
{
    private const AIRDROP_REWARD_PRECISION = 4;
    private const ONE_HOUR_IN_SEC = 3600;

    private EntityManagerInterface $em;
    private AirdropParticipantRepository $participantRepository;
    private MoneyWrapperInterface $moneyWrapper;
    private BalanceHandlerInterface $balanceHandler;
    private EventDispatcherInterface $eventDispatcher;
    private AirdropRepository $airdropRepository;
    private TokenManagerInterface $tokenManager;
    private SessionInterface $session;

    public function __construct(
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        BalanceHandlerInterface $balanceHandler,
        EventDispatcherInterface $eventDispatcher,
        TokenManagerInterface $tokenManager,
        SessionInterface $session
    ) {
        $this->em = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->balanceHandler = $balanceHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenManager = $tokenManager;
        $this->session = $session;

        /** @var AirdropParticipantRepository $objRepository */
        $objRepository = $entityManager->getRepository(AirdropParticipant::class);
        $this->participantRepository = $objRepository;

        /** @var AirdropRepository $objRepository */
        $objRepository = $entityManager->getRepository(Airdrop::class);
        $this->airdropRepository = $objRepository;
    }

    public function createAirdrop(
        Token $token,
        Money $amount,
        int $participants,
        ?\DateTimeImmutable $endDate = null
    ): Airdrop {
        $user = $token->getProfile()->getUser();
        $this->checkUserBalance($user, $token, $amount);

        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        $airdrop->setToken($token);
        $airdrop->setAmount($amount);
        $airdrop->setParticipants($participants);

        if ($endDate instanceof \DateTimeImmutable && $endDate->getTimestamp() > time()) {
            if ($endDate->getTimestamp() - time() < self::ONE_HOUR_IN_SEC) {
                $newEndDate = new \DateTimeImmutable('+1 hour');
                $airdrop->setEndDate($newEndDate);
            } else {
                $airdrop->setEndDate($endDate);
            }
        }

        $reward = $this->getAirdropReward($airdrop);
        $lockedAmount = $reward->multiply($participants);
        $lockedAmount = $lockedAmount->add($reward->divide(2));
        $airdrop->setLockedAmount($lockedAmount);

        try {
            $this->balanceHandler->beginTransaction();
            // Lock tokens for airdrop campaign
            $this->balanceHandler->update(
                $user,
                $token,
                $lockedAmount->negative(),
                'airdrop_amount'
            );
        } catch (\Throwable $e) {
            $this->balanceHandler->rollback();

            throw $e;
        }

        $this->em->persist($airdrop);
        $this->em->flush();

        return $airdrop;
    }

    public function deleteAirdrop(Airdrop $airdrop): void
    {
        /** @var Token $token */
        $token = $airdrop->getToken();
        $airdrop->setStatus(Airdrop::STATUS_REMOVED);

        $amountToReturn = $this->getRestOfTokens($airdrop);

        if ($amountToReturn) {
            try {
                $this->balanceHandler->beginTransaction();
                $this->balanceHandler->update(
                    $token->getProfile()->getUser(),
                    $token,
                    $amountToReturn,
                    'airdrop_amount'
                );
            } catch (\Throwable $e) {
                $this->balanceHandler->rollback();

                throw $e;
            }
        }

        if ($airdrop->getActualParticipants() > 0) {
            $token->setAirdropsAmount(
                $token->getAirdropsAmount()->add($airdrop->getActualAmount())
            );
            $this->em->persist($token);
        }

        $this->airdropRepository->deleteReferralCodes($airdrop);

        $this->em->persist($airdrop);
        $this->em->flush();

        /** @psalm-suppress TooManyArguments */
        $this->eventDispatcher->dispatch(
            new AirdropEvent($airdrop, ActivityTypes::AIRDROP_ENDED),
            TokenEvents::AIRDROP_ENDED
        );
    }

    public function deleteActiveAirdrop(Token $token): void
    {
        $existingAirdrop = $token->getActiveAirdrop();

        if ($existingAirdrop && Airdrop::STATUS_ACTIVE === $existingAirdrop->getStatus()) {
            $this->deleteAirdrop($existingAirdrop);
        }
    }

    public function checkIfUserClaimed(?User $user, Token $token): bool
    {
        if ($user instanceof User && $token->getActiveAirdrop() instanceof Airdrop) {
            $participant = $this->participantRepository
                ->getParticipantByUserAndAirdrop($user, $token->getActiveAirdrop());

            return $participant instanceof AirdropParticipant;
        }

        return false;
    }

    public function claimAirdropCampaign(User $user, Token $token): void
    {
        /** @var Airdrop $activeAirdrop */
        $activeAirdrop = $token->getActiveAirdrop();
        $activeAirdrop->incrementActualParticipants();
        $airdropReward = $activeAirdrop->getReward();

        $this->tokenBlockAirDropBalance($activeAirdrop);

        $participant = $this->createNewParticipant(
            $user,
            $activeAirdrop
        );

        try {
            $this->balanceHandler->beginTransaction();

            if ($user->getAirdropReferrer() === $activeAirdrop) {
                $referrer = $user->getAirdropReferrerUser();

                $rewardedForUserReferrer = $this->participantRepository
                ->getParticipantByUserAndAirdrop($referrer, $activeAirdrop, $user);

                if (!$rewardedForUserReferrer) {
                    $activeAirdrop->incrementActualParticipants(true);
                    $referralParticipant = $this->createNewParticipant(
                        $referrer,
                        $activeAirdrop,
                        $user
                    );

                    $this->em->persist($referralParticipant);

                    $this->balanceHandler->depositBonus(
                        $referrer,
                        $token,
                        $airdropReward->divide(2),
                        BalanceTransactionBonusType::AIRDROP_REFFERAL
                    );

                    $this->eventDispatcher->dispatch(new UserAirdropEvent(
                        $activeAirdrop,
                        $referrer,
                        ActivityTypes::AIRDROP_REFERRAL
                    ), TokenEvents::AIRDROP_CLAIMED);
                }
            }

            $rewardSummary = $airdropReward->multiply($activeAirdrop->getActualParticipants());
            $activeAirdrop->setActualAmount($rewardSummary);


            $this->balanceHandler->depositBonus(
                $user,
                $token,
                $airdropReward,
                BalanceTransactionBonusType::AIRDROP_REWARD
            );

            $this->em->persist($activeAirdrop);
            $this->em->persist($participant);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->balanceHandler->rollback();

            throw $e;
        }

        if ($activeAirdrop->getParticipants() - $activeAirdrop->getActualParticipants() < 1) {
            $this->deleteAirdrop($activeAirdrop);
        }
    }

    public function getAirdropReward(Airdrop $airdrop): Money
    {
        $amount = $airdrop->getAmount();
        $participants = $this->moneyWrapper->parse(
            (string)$airdrop->getParticipants(),
            Symbols::TOK
        );

        if ($amount->isZero() || !$airdrop->getParticipants()) {
            throw new InvalidArgumentException('Airdrop reward calculation failed.');
        }

        $reward = $this->roundAirdropReward(
            $amount->ratioOf($participants)
        );

        return $this->moneyWrapper->parse(
            $reward,
            Symbols::TOK
        );
    }

    public function updateOutdatedAirdrops(): int
    {
        /** @var Airdrop[] $outdatedAirdrops */
        $outdatedAirdrops = $this->airdropRepository->getOutdatedAirdrops();

        foreach ($outdatedAirdrops as $outdatedAirdrop) {
            $this->deleteAirdrop($outdatedAirdrop);
        }

        return count($outdatedAirdrops);
    }

    public function tokenBlockAirDropBalance(Airdrop $activeAirdrop): void
    {
        $airdropAmount = $activeAirdrop->getAmount();
        $airdropActualAmount = $activeAirdrop->getActualAmount();

        if ($airdropAmount->equals($airdropActualAmount)) {
            throw new ApiBadRequestException('Insufficient funds.');
        }
    }

    public function createAction(string $action, ?string $actionData, Airdrop $airdrop): void
    {
        $action = (new AirdropAction())
            ->setType(AirdropAction::TYPE_MAP[$action])
            ->setAirdrop($airdrop)
            ->setData($actionData);

        $this->em->persist($action);
        $this->em->flush();
    }

    public function claimAirdropAction(AirdropAction $action, User $user): void
    {
        $action->addUser($user);

        $this->em->persist($action);
        $this->em->flush();
    }

    public function checkIfUserCompletedActions(Airdrop $airdrop, User $user): bool
    {
        return $airdrop
            ->getActions()
            ->forAll(fn (int $key, AirdropAction $action) => $action->getUsers()->contains($user));
    }

    private function createNewParticipant(User $user, Airdrop $airdrop, ?User $referral = null): AirdropParticipant
    {
        return (new AirdropParticipant())
            ->setUser($user)
            ->setAirdrop($airdrop)
            ->setReferral($referral);
    }

    private function getRestOfTokens(Airdrop $airdrop): ?Money
    {
        $diffAmount = $airdrop->getLockedAmount()->subtract($airdrop->getActualAmount());
        $zeroValue = new Money(0, new Currency(Symbols::TOK));

        if ($diffAmount->greaterThan($zeroValue)) {
            return $diffAmount;
        }

        return null;
    }

    private function roundAirdropReward(string $amount, int $precision = self::AIRDROP_REWARD_PRECISION): string
    {
        $dotPosition = intval(strpos($amount, '.'));

        if (0 !== $dotPosition) {
            $amount = substr($amount, 0, $dotPosition + $precision + 1);
        }

        return $amount;
    }

    private function checkUserBalance(User $user, Token $token, Money $amount): void
    {
        $balance = $this->balanceHandler->exchangeBalance($user, $token);

        if ($balance->lessThan($amount)) {
            throw new ApiBadRequestException('Insufficient funds.');
        }
    }

    public function claimAirdropsActionsFromSessionData(User $user): void
    {
        if (!$airdropCompletedActions = $this->session->get('airdrops', [])) {
            return;
        }

        foreach ($airdropCompletedActions as $tokenName => $airdropSessionData) {
            /** @var Token $token */
            $token = $this->tokenManager->findByName((string)$tokenName);

            if ($this->checkIfUserClaimed($user, $token)) {
                continue;
            }

            $this->doClaimAirdropActions($airdropSessionData, $user);
        }

        $this->session->remove('airdrops');
    }

    private function doClaimAirdropActions(array $airdropSessionData, User $user): void
    {
        $airdropActionRepository = $this->em->getRepository(AirdropAction::class);

        foreach ($airdropSessionData as $airdropActionId) {
            /** @var AirdropAction $airdropAction */
            $airdropAction = $airdropActionRepository->find($airdropActionId);

            if (Airdrop::STATUS_REMOVED === $airdropAction->getAirdrop()->getStatus()) {
                continue;
            }

            if (in_array($user, $airdropAction->getUsers()->getValues(), true)) {
                continue;
            }

            $this->claimAirdropAction($airdropAction, $user);
        }
    }
}
