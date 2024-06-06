<?php declare(strict_types = 1);

namespace App\Wallet\Model;

/** @codeCoverageIgnore */
class BlockchainTransaction
{
    private string $hash;
    private array $toAmounts;
    private ?string $fee;

    private function __construct(
        string $hash,
        array $toAmounts,
        ?string $fee
    ) {
        $this->hash = $hash;
        $this->toAmounts = $toAmounts;
        $this->fee = $fee;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getFee(): ?string
    {
        return $this->fee;
    }

    public function getToAmounts(): array
    {
        return $this->toAmounts;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['hash'] ?? '',
            $data['toAmounts'],
            $data['fee'] ?? null
        );
    }
}
