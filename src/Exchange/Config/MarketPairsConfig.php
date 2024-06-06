<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Converter\RebrandingConverterInterface;

class MarketPairsConfig
{
    private RebrandingConverterInterface $rebrandingConverter;
    private array $enabledMarkets;
    private array $enabledTopListMarkets;

    public function __construct(
        RebrandingConverterInterface $rebrandingConverter,
        array $enabledMarkets,
        array $enabledTopListMarkets
    ) {
        $this->rebrandingConverter = $rebrandingConverter;
        $this->enabledMarkets = $enabledMarkets;
        $this->enabledTopListMarkets = $enabledTopListMarkets;
    }

    public function getParsedEnabledPairs(): array
    {
        return $this->parsePairs($this->enabledMarkets);
    }

    public function getJoinedTopListPairs(): array
    {
        return array_map(
            fn($pair) => $pair['quote'] . $pair['base'],
            $this->parsePairs($this->enabledTopListMarkets)
        );
    }

    public function isMarketPairEnabled(string $base, string $quote): bool
    {
        return in_array($quote.$base, $this->getParsedEnabledPairsKeys());
    }

    public function getEnabledPairsByQuote(string $quote): array
    {
        return array_filter($this->getParsedEnabledPairs(), fn ($pair) => $pair['quote'] === $quote);
    }

    private function parsePairs(array $pairs): array
    {
        return array_reduce($pairs, function ($result, $pair) {
            $substrs = explode('/', $pair);

            if (count($substrs) < 2 || $substrs[0] === $substrs[1]) {
                return $result;
            }

            $result[] = [
                'quote' => $this->rebrandingConverter->reverseConvert($substrs[0]),
                'base' => $this->rebrandingConverter->reverseConvert($substrs[1]),
            ];

            return $result;
        }, []);
    }

    private function getParsedEnabledPairsKeys(): array
    {
        return array_map(fn($pair) => $pair['quote'] . $pair['base'], $this->getParsedEnabledPairs());
    }
}
