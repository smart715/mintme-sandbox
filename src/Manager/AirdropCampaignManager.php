<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\AirdropCampaign\AirdropParticipantRepository;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;

class AirdropCampaignManager implements AirdropCampaignManagerInterface
{
    private const AIRDROP_REWARD_PRECISION = 4;

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
        $this->participantRepository = $entityManager->getRepository(AirdropParticipant::class);
    }

    public function createAirdrop(
        Token $token,
        Money $amount,
        int $participants,
        ?\DateTimeImmutable $endDate = null
    ): Airdrop {
        $this->deleteActiveAirdrop($token);

        $airdrop = new Airdrop();
        $airdrop->setStatus(Airdrop::STATUS_ACTIVE);
        $airdrop->setToken($token);
        $airdrop->setAmount(
            $this->moneyWrapper->format($amount)
        );
        $airdrop->setParticipants($participants);

        if ($endDate instanceof \DateTimeImmutable && $endDate->getTimestamp() > time()) {
            $airdrop->setEndDate($endDate);
        }

        $this->em->persist($airdrop);
        $this->em->flush();

        // Lock tokens for airdrop campaign
        $this->balanceHandler->update(
            $token->getProfile()->getUser(),
            $token,
            $amount->negative(),
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
            $airdropsSummary = $this->moneyWrapper->parse(
                $token->getAirdropsAmount(),
                MoneyWrapper::TOK_SYMBOL
            );
            $actualAmount = $this->moneyWrapper->parse(
                (string)$airdrop->getActualAmount(),
                MoneyWrapper::TOK_SYMBOL
            );

            $airdropsSummary = $airdropsSummary->add($actualAmount);
            $token->setAirdropsAmount($this->moneyWrapper->format($airdropsSummary));
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
                ->getParticipantByUserAndToken($user, $token->getActiveAirdrop());

            return $participant instanceof AirdropParticipant;
        }

        return false;
    }

    public function claimAirdropCampaign(User $user, Token $token): void
    {
        /** @var Airdrop $activeAirdrop */
        $activeAirdrop = $token->getActiveAirdrop();
        $activeAirdrop->incrementActualParticipants();
        $airdropReward = $this->getAirdropReward($activeAirdrop);

        $this->balanceHandler->update($user, $token, $airdropReward, 'reward');

        $rewardSummary = $airdropReward->multiply((int)$activeAirdrop->getActualParticipants());
        $activeAirdrop->setActualAmount(
            $this->roundAirdropReward(
                $this->moneyWrapper->format($rewardSummary)
            )
        );
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
        $amount = $this->moneyWrapper->parse(
            $airdrop->getAmount(),
            MoneyWrapper::TOK_SYMBOL
        );
        $participants = $this->moneyWrapper->parse(
            (string)$airdrop->getParticipants(),
            MoneyWrapper::TOK_SYMBOL
        );

        if ($amount->isZero() || !$airdrop->getActualParticipants()) {
            throw new InvalidArgumentException('Airdrop reward calculation failed.');
        }

        return $this->moneyWrapper->parse(
            $amount->ratioOf($participants),
            MoneyWrapper::TOK_SYMBOL
        );
    }

    private function createNewParticipant(User $user, Airdrop $airdrop): AirdropParticipant
    {
        return (new AirdropParticipant())
            ->setUser($user)
            ->setAirdrop($airdrop);
    }

    private function getRestOfTokens(Airdrop $airdrop): ?Money
    {
        $amount = $this->moneyWrapper->parse(
            $airdrop->getAmount(),
            MoneyWrapper::TOK_SYMBOL
        );
        $actualAmount = $this->moneyWrapper->parse(
            (string)$airdrop->getActualAmount(),
            MoneyWrapper::TOK_SYMBOL
        );

        $diffAmount = $amount->subtract($actualAmount);
        $zeroValue = new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL));

        if ($diffAmount->greaterThan($zeroValue)) {
            return $diffAmount;
        }

        return null;
    }

    private function roundAirdropReward(string $amount): string
    {
        //Round rewards down and up to 4th decimal
        return (string)round(floatval($amount), self::AIRDROP_REWARD_PRECISION, PHP_ROUND_HALF_DOWN);
    }
}
