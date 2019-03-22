<?php declare(strict_types = 1);

namespace App\Deposit\Model;

class DepositCallbackMessage
{
    /** @var int */
    private $id;

    /** @var string */
    private $crypto;

    /** @var string */
    private $amount;

    private function __construct(
        int $id,
        string $crypto,
        string $amount
    ) {
        $this->id = $id;
        $this->crypto = $crypto;
        $this->amount = $amount;
    }

    public function getUserId(): int
    {
        return $this->id;
    }

    public function getCrypto(): string
    {
        return $this->crypto;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['userId'],
            $data['crypto'],
            $data['amount']
        );
    }

    public function getMessageWithIncrementedRetryCount(): self
    {
        return new self(
            $this->id,
            $this->crypto,
            $this->amount
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getUserId(),
            'crypto' => $this->getCrypto(),
            'amount' => $this->getAmount(),
        ];
    }
}
