<?php declare(strict_types = 1);

namespace App\SmartContract\Config;

/** @codeCoverageIgnore */
class ExplorerUrlsConfig implements ExplorerUrlsConfigInterface
{
    private array $explorerUrls;

    public function __construct(array $explorerUrls)
    {
        $this->explorerUrls = $explorerUrls;
    }

    public function getExplorerUrl(string $symbol, string $hash): string
    {
        $explorerUrl = $this->explorerUrls[$symbol];

        if (!$explorerUrl) {
            return '';
        }

        return $this->getTxUrl($explorerUrl, $hash);
    }

    private function getTxUrl(string $url, string $hash): string
    {
        return "{$url}/tx/{$hash}";
    }
}
