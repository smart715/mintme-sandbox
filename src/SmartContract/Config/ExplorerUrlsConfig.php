<?php declare(strict_types = 1);

namespace App\SmartContract\Config;

use App\Utils\Symbols;

/** @codeCoverageIgnore */
class ExplorerUrlsConfig implements ExplorerUrlsConfigInterface
{
    private string $mintmeExplorerUrl;
    private string $ethExplorerUrl;
    private string $bnbExplorerUrl;

    public function __construct(string $mintmeExplorerUrl, string $ethExplorerUrl, string $bnbExplorerUrl)
    {
        $this->mintmeExplorerUrl = $mintmeExplorerUrl;
        $this->ethExplorerUrl = $ethExplorerUrl;
        $this->bnbExplorerUrl = $bnbExplorerUrl;
    }

    public function getExplorerUrl(string $symbol, string $hash): string
    {
        return $this->getExplorerUrlsMap($hash)[$symbol] ?? '';
    }

    private function getExplorerUrlsMap(string $hash): array
    {
        return [
            Symbols::WEB => $this->getTxUrl($this->mintmeExplorerUrl, $hash),
            Symbols::ETH => $this->getTxUrl($this->ethExplorerUrl, $hash),
            Symbols::BNB => $this->getTxUrl($this->bnbExplorerUrl, $hash),
        ];
    }

    private function getTxUrl(string $url, string $hash): string
    {
        return "{$url}/tx/{$hash}";
    }
}
