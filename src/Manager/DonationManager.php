<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\LimitHistoryConfig;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Repository\DonationRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

class DonationManager implements DonationManagerInterface
{
    private DonationRepository $repository;
    private LimitHistoryConfig $limitHistoryConfig;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        DonationRepository $donationRepository,
        LimitHistoryConfig $limitHistoryConfig,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->repository = $donationRepository;
        $this->limitHistoryConfig = $limitHistoryConfig;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritDoc} */
    public function getUserRelated(User $user, int $offset, int $limit): array
    {
        return $this->repository->findUserRelated($user, $offset, $limit, $this->limitHistoryConfig->getFromDate());
    }

    public function getDirectBuyVolume(Token $token): Money
    {
        $allDirectBuy = $this->repository->getAllDirectBuy($token);

        return array_reduce($allDirectBuy, function (Money $sum, array $result) {
            $tokenAmount = $result['tokenAmount'];

            if ($tokenAmount) {
                $tokenAmountMoneyObj = new Money($tokenAmount, new Currency(Symbols::TOK));

                $sum = $sum->add($tokenAmountMoneyObj);
            }

            return $sum;
        }, new Money(0, new Currency(Symbols::TOK)));
    }

    public function getDonationReferralRewards(User $user): Money
    {
        $allReferralRewards = $this->repository->getUserDonationRewards($user);

        return array_reduce($allReferralRewards, function (Money $sum, array $result) {
            $referencerAmount = $result['referencer_amount'];

            if ($referencerAmount) {
                $referencerAmountMoneyObj = new Money(
                    $this->moneyWrapper->convertToDecimalIfNotation($referencerAmount, Symbols::WEB),
                    new Currency(Symbols::WEB)
                );

                $sum = $sum->add($referencerAmountMoneyObj);
            }

            return $sum;
        }, new Money(0, new Currency(Symbols::WEB)));
    }

    public function getTotalRewardsGiven(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->repository->getTotalRewardsGiven($from, $to);
    }
}
