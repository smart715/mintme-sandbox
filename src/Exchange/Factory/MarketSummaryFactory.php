<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Exchange\Market;
use App\Exchange\Market\Model\Summary;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;

/** @codeCoverageIgnore */
class MarketSummaryFactory implements MarketSummaryFactoryInterface
{
    private array $result;
    private MoneyWrapperInterface $moneyWrapper;
    private MarketNameConverterInterface $nameConverter;

    /** @var Market[] */
    private $indexedMarkets;

    public function __construct(
        array $result,
        array $markets,
        MoneyWrapperInterface $moneyWrapper,
        MarketNameConverterInterface $nameConverter
    ) {
        $this->result = $result;
        $this->moneyWrapper = $moneyWrapper;
        $this->nameConverter = $nameConverter;

        $this->initIndexedMarkets($markets);
    }

    public function create(): array
    {
        return array_map(function ($item) {
            $market = $this->getMarket($item['name']);

            $symbol = $market->isTokenMarket()
                ? Symbols::TOK
                : $market->getQuote()->getSymbol();

            return new Summary(
                (int)$item['ask_count'],
                $this->moneyWrapper->parse($item['ask_amount'], $symbol),
                (int)$item['bid_count'],
                $this->moneyWrapper->parse($item['bid_amount'], $symbol),
            );
        }, $this->result);
    }

    private function getMarket(string $name): Market
    {
        return $this->indexedMarkets[$name];
    }

    /**
     * @param Market[] $markets
     */
    private function initIndexedMarkets(array $markets): void
    {
        $indexedMarkets = [];

        foreach ($markets as $market) {
            $indexedMarkets[$this->nameConverter->convert($market)] = $market;
        }

        $this->indexedMarkets = $indexedMarkets;
    }
}
