<?php declare(strict_types = 1);

namespace App\Wallet\Model;

/** @codeCoverageIgnore */
class MarketCallbackMessage
{
    private int $retried;
    private string $base;
    private string $quote;
    private ?int $userId;

    private function __construct(
        int $retried,
        string $base,
        string $quote,
        ?int $userId
    ) {
        $this->retried = $retried;
        $this->base = $base;
        $this->quote = $quote;
        $this->userId = $userId;
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

    public function getUserId(): ?int
    {
        return $this->userId;
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
            $data['user_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'retried' => $this->getRetried(),
            'base' => $this->getBase(),
            'quote' => $this->getQuote(),
            'user_id' => $this->getUserId(),
        ];
    }
}
