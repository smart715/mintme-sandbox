<?php declare(strict_types = 1);

namespace App\Wallet\Deposit\Model;

/** @codeCoverageIgnore */
class DepositCallbackMessage
{
    private int $userId;
    private array $hashes;
    private string $amount;
    private ?string $forwardedAmount;
    private string $address;

    private string $asset;
    private string $cryptoNetwork;

    private function __construct(
        int $userId,
        array $hashes,
        string $amount,
        ?string $forwardedAmount,
        string $address,
        string $asset,
        string $cryptoNetwork
    ) {
        $this->userId = $userId;
        $this->hashes = $hashes;
        $this->amount = $amount;
        $this->forwardedAmount = $forwardedAmount;
        $this->address = $address;
        $this->asset = $asset;
        $this->cryptoNetwork = $cryptoNetwork;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getHashes(): array
    {
        return $this->hashes;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getForwardedAmount(): ?string
    {
        return $this->forwardedAmount;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getAsset(): string
    {
        return $this->asset;
    }

    public function getCryptoNetwork(): string
    {
        return $this->cryptoNetwork;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['userId'],
            $data['hashes'],
            $data['amount'],
            $data['forwardedAmount'],
            $data['address'],
            $data['asset'],
            $data['cryptoNetwork'],
        );
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->getUserId(),
            'hashes' => $this->getHashes(),
            'amount' => $this->getAmount(),
            'forwardedAmount' => $this->forwardedAmount,
            'address' => $this->getAddress(),
            'asset' => $this->getAsset(),
            'cryptoNetwork' => $this->getCryptoNetwork(),
        ];
    }
}
