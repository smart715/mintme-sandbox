<?php declare(strict_types = 1);

namespace App\SmartContract\Config;

/** @codeCoverageIgnore */
interface ExplorerUrlsConfigInterface
{
    public function getExplorerUrl(string $symbol, string $hash): string;
}
