<?php declare(strict_types = 1);

namespace App\Utils;

/** @codeCoverageIgnore */
final class Symbols
{
    public const TOK = "TOK";
    public const WEB = "WEB";
    public const MINTME = "MINTME";
    public const BTC = "BTC";
    public const ETH = "ETH";
    public const USD = "USD";
    public const USDC = "USDC";
    public const BNB = "BNB";
    public const BSC = "BSC";
    public const CRO = "CRO";
    public const SOL = "SOL";
    public const AVAX = "AVAX";
    public const ARB = "ARB";
    public const BASE = "BASE";

    // TODO: remove this constant, should be replaced by gateway config
    public const ETH_BASED = [self::ETH, self::BNB, self::WEB, self::CRO, self::AVAX, self::ARB, self::BASE];

    private function __construct()
    {
    }
}
