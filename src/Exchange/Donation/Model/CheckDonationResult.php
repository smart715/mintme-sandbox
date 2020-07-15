<?php declare(strict_types = 1);

namespace App\Exchange\Donation\Model;

/** @codeCoverageIgnore */
class CheckDonationResult
{
    /** @var string */
    private $expectedTokens;

    /** @var string */
    private $tokensWorth;

    public function __construct(string $expectedTokens = '0', string $tokensWorth = '0')
    {
        $this->expectedTokens = $expectedTokens;
        $this->tokensWorth = $tokensWorth;
    }

    public function getExpectedTokens(): string
    {
        return $this->expectedTokens;
    }

    public function getTokensWorth(): string
    {
        return $this->tokensWorth;
    }
}