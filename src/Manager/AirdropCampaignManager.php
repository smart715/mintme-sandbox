<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Repository\AirdropCampaign\AirdropRepository;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;

class AirdropCampaignManager implements AirdropCampaignManagerInterface
{
    private const AIRDROP_REWARD_PRECISION = 4;
    private const ONE_HOUR_IN_MILLISEC = 86400000;

    /** @var EntityManagerInterface */
    private $em;

    /** @var AirdropParticipantRepository */
    private $participantRepository;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        MoneyWrapperInterface $moneyWrapper,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->em = $entityManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->balanceHandler = $balanceHandler;

        /** @var AirdropParticipantRepository */
        $objRepository = $entityManager->getRepository(AirdropParticipant::class);
        $this->participantRepository = $objRepository;
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

        if ($endDate instanceof \DateTimeImmutable && ($endDate->getTimestamp() - time()) < self::ONE_HOUR_IN_MILLISEC){
            $newEndDate = $endDate->setTime(0, 0, [0, [(time() + self::ONE_HOUR_IN_MILLISEC)]]);
            $airdrop->setEndDate($newEndDate);
        }
        elseif ($endDate instanceof \DateTimeImmutable && $endDate->getTimestamp() > time()) {
            $airdrop->setEndDate($endDate);
        }


        $reward = $this->getAirdropReward($airdrop);
        $lockedAmount = $reward->multiply($participants);
        $airdrop->setLockedAmount($lockedAmount);

        $this->em->persist($airdrop);
        $this->em->flush();

        // Lock tokens for airdrop campaign
        $this->balanceHandler->update(
            $user,
            $token,
            $lockedAmount->negative(),
            'airdrop_amount'
        );

        return $airdrop;
    }

    public function deleteAirdrop(Airdrop $airdrop): void
    {
        /** @var Token $token */
        $token = $airdrop->getToken();
        $airdrop->setStatus(Airdrop::STATUS_REMOVED);

        $amountToReturn = $this->getRestOfTokens($airdrop);

        if ($amountToReturn) {
            $this->balanceHandler->update(
                $token->getProfile()->getUser(),
                $token,
                $amountToReturn,
                'airdrop_amount'
            );
        }

        if ($airdrop->getActualParticipants() > 0) {
            $token->setAirdropsAmount(
                $token->getAirdropsAmount()->add($airdrop->getActualAmount())
            );
            $this->em->persist($token);
        }

        $this->em->persist($airdrop);
        $this->em->flush();
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
        $airdropReward = $activeAirdrop->getLockedAmount()
            ->divide($activeAirdrop->getParticipants());

        $this->checkUserBalance(
            $token->getProfile()->getUser(),
            $token,
            $airdropReward
        );

        $this->balanceHandler->update($user, $token, $airdropReward, 'reward');

        $rewardSummary = $airdropReward->multiply($activeAirdrop->getActualParticipants());
        $activeAirdrop->setActualAmount($rewardSummary);
        $participant = $this->createNewParticipant($user, $activeAirdrop);

        $this->em->persist($activeAirdrop);
        $this->em->persist($participant);
        $this->em->flush();

        if ($activeAirdrop->getParticipants() === $activeAirdrop->getActualParticipants()) {
            $this->deleteAirdrop($activeAirdrop);
        }
    }

    public function getAirdropReward(Airdrop $airdrop): Money
    {
        $amount = $airdrop->getAmount();
        $participants = $this->moneyWrapper->parse(
            (string)$airdrop->getParticipants(),
            MoneyWrapper::TOK_SYMBOL
        );

        if ($amount->isZero() || !$airdrop->getParticipants()) {
            throw new InvalidArgumentException('Airdrop reward calculation failed.');
        }

        $reward = $this->roundAirdropReward(
            $amount->ratioOf($participants)
        );

        return $this->moneyWrapper->parse(
            $reward,
            MoneyWrapper::TOK_SYMBOL
        );
    }

    public function updateOutdatedAirdrops(): int
    {
        /** @var AirdropRepository $repository */
        $repository = $this->em->getRepository(Airdrop::class);
        /** @var Airdrop[] $outdatedAirdrops */
        $outdatedAirdrops = $repository->getOutdatedAirdrops();

        foreach ($outdatedAirdrops as $outdatedAirdrop) {
            $this->deleteAirdrop($outdatedAirdrop);
        }

        return count($outdatedAirdrops);
    }

    private function createNewParticipant(User $user, Airdrop $airdrop): AirdropParticipant
    {
        return (new AirdropParticipant())
            ->setUser($user)
            ->setAirdrop($airdrop);
    }

    private function getRestOfTokens(Airdrop $airdrop): ?Money
    {
        $diffAmount = $airdrop->getLockedAmount()->subtract($airdrop->getActualAmount());
        $zeroValue = new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL));

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
}
