<?php declare(strict_types = 1);

namespace App\Utils;

use App\Exception\CryptoCalculatorException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

class CryptoCalculator
{
    private MoneyWrapperInterface $moneyWrapper;
    private MarketHandlerInterface $marketHandler;
    private CryptoManagerInterface $cryptoManager;
    private MarketFactoryInterface $marketFactory;

    public function __construct(
        MoneyWrapperInterface $moneyWrapper,
        MarketHandlerInterface $marketHandler,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory
    ) {
        $this->moneyWrapper = $moneyWrapper;
        $this->marketHandler = $marketHandler;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
    }

    public function getMintmeWorth(Money $cryptoAmount, bool $notEnoughOrdersError = false): Money
    {
        $cryptoMarket = $this->marketFactory->create(
            $this->cryptoManager->findBySymbol($cryptoAmount->getCurrency()->getCode()),
            $this->cryptoManager->findBySymbol(Symbols::WEB)
        );

        $pendingSellOrders = $this->marketHandler->getAllPendingSellOrders($cryptoMarket);

        $totalSum = $this->moneyWrapper->parse('0', $cryptoAmount->getCurrency()->getCode());
        $mintmeWorth = $this->moneyWrapper->parse('0', Symbols::WEB);

        foreach ($pendingSellOrders as $sellOrder) {
            if ($totalSum->greaterThanOrEqual($cryptoAmount)) {
                break;
            }

            $order = $sellOrder->getPrice()->multiply(
                $this->moneyWrapper->format($sellOrder->getAmount())
            );

            $diff = $cryptoAmount->subtract($totalSum);

            if ($diff->greaterThan($order)) {
                $totalSum = $totalSum->add($order);
                $mintmeWorth = $mintmeWorth->add($sellOrder->getAmount());
            } else {
                $order = $diff->divide($this->moneyWrapper->format($sellOrder->getPrice()));
                $order = new Money($order->getAmount(), new Currency(Symbols::WEB));

                $totalSum = $totalSum->add($diff);
                $mintmeWorth = $mintmeWorth->add($order);
            }
        }

        if ($notEnoughOrdersError && $totalSum->lessThan($cryptoAmount)) {
            throw CryptoCalculatorException::notEnoughOrders();
        }

        return $mintmeWorth;
    }
}
