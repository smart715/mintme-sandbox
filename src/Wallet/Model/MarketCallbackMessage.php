<?php declare(strict_types = 1);

namespace App\Wallet\Model;

class MarketCallbackMessage
{
    /** @var int */
    private $retried;

    /** @var string */
    private $base;

    /** @var string */
    private $quote;

    private function __construct(
        int $retried,
        string $base,
        string $quote
    ) {
        $this->retried = $retried;
        $this->base = $base;
        $this->quote = $quote;
    }

    public function getRetried(): int
    {
        return $this->retried;
    }

    public function getBase(): string
    {
        return $this->base;
    }

    public function getQuote(): string
    {
        return $this->quote;
    }

    public function incrementRetries(): int
    {
        return ++$this->retried;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['retried'],
            $data['base'],
            $data['quote'],
        );
    }

    public function toArray(): array
    {
        return [
            'retried' => $this->getRetried(),
            'base' => $this->getBase(),
            'quote' => $this->getQuote(),
        ];
    }
}
