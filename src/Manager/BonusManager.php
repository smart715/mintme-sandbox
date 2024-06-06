<?php declare(strict_types = 1);

namespace App\Manager;

use App\Repository\BonusRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

class BonusManager implements BonusManagerInterface
{

    private BonusRepository $bonusRepository;
    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        BonusRepository $bonusRepository,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->bonusRepository = $bonusRepository;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function isLimitReached(string $limit, string $type): bool
    {
        return $this->moneyWrapper->parse(
            $limit,
            Symbols::WEB
        )->lessThanOrEqual(
            new Money($this->moneyWrapper->convertToDecimalIfNotation(
                $this->bonusRepository->getPaidSum($type),
                Symbols::WEB
            ), new currency(Symbols::WEB))
        );
    }
}
