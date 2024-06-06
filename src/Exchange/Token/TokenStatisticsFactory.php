<?php declare(strict_types = 1);

namespace App\Exchange\Token;

use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Token\Model\TokenStatisticsModel;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;

class TokenStatisticsFactory implements TokenStatisticsFactoryInterface
{
    private BalanceHandlerInterface $balanceHandler;
    private MarketHandlerInterface $marketHandler;
    private MarketStatusManagerInterface $marketStatusManager;
    private MarketFactoryInterface $marketFactory;
    private LoggerInterface $logger;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        MarketHandlerInterface $marketHandler,
        MarketStatusManagerInterface $marketStatusManager,
        MarketFactoryInterface $marketFactory,
        LoggerInterface $logger
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->marketHandler = $marketHandler;
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFactory = $marketFactory;
        $this->logger = $logger;
    }

    public function create(Token $token, Market $mintmeMarket): TokenStatisticsModel
    {
        $markets = $this->marketFactory->createTokenMarkets($token);
        $tokenExchangeAmount = $this->getExchangeBalance($token);
        $totalSellOrders = $this->getTotalSellOrdersSummary($markets);
        $totalBuyOrders = $this->getBuyOrdersSummary($mintmeMarket);
        $marketStatus = $this->getMarketStatus($mintmeMarket);
        $volumeDonation = $this->getVolumeDonation($markets);
        $holders = $this->getHolders($token);

        return new TokenStatisticsModel(
            $tokenExchangeAmount,
            $totalSellOrders,
            $totalBuyOrders,
            $marketStatus ? $marketStatus->getSoldOnMarket() : null,
            $volumeDonation,
            $holders,
        );
    }

    private function getMarketStatus(Market $market): ?MarketStatus
    {
        try {
            return $this->marketStatusManager->getOrCreateMarketStatus($market);
        } catch (\Throwable $error) {
            $this->logger->error(
                'Failed to get market status: ' .
                (string)$market . ' ' .
                $error->getMessage()
            );

            return null;
        }
    }

    private function getBuyOrdersSummary(Market $market): ?string
    {
        try {
            return $this->marketHandler->getBuyOrdersSummary($market)->getBasePrice();
        } catch (\Throwable $error) {
            $this->logger->error(
                'Failed to get market buy orders summary: ' .
                (string)$market . ' ' .
                $error->getMessage()
            );

            return null;
        }
    }

    private function getSellOrdersSummary(Market $market): ?string
    {
        try {
            return $this->marketHandler->getSellOrdersSummary($market)->getQuoteAmount();
        } catch (\Throwable $error) {
            $this->logger->error(
                'Failed to get market sell orders summary: ' .
                (string)$market . ' ' .
                $error->getMessage()
            );

            return null;
        }
    }

    private function getExchangeBalance(Token $token): ?Money
    {
        try {
            return $this->balanceHandler->exchangeBalance($token->getProfile()->getUser(), $token);
        } catch (\Throwable $error) {
            $this->logger->error('Failed to get token exchange balance' . $error->getMessage());

            return null;
        }
    }

    /**
     * @param Market[] $markets
     */
    private function getVolumeDonation(array $markets): Money
    {
        return array_reduce($markets, function (Money $carry, Market $market) {
            $marketStatus = $this->getMarketStatus($market);

            if ($marketStatus) {
                $carry = $carry->add($marketStatus->getVolumeDonation());
            }

            return $carry;
        }, new Money(0, new Currency(Symbols::TOK)));
    }

    private function getTotalSellOrdersSummary(array $markets): string
    {
        return array_reduce($markets, function ($totalSellOrders, Market $market) {
            $totalSellOrders = bcadd($totalSellOrders, $this->getSellOrdersSummary($market) ?? '0');

            return $totalSellOrders;
        }, '0');
    }

    private function getHolders(Token $token): int
    {
        return $token->getHoldersCount();
    }
}
