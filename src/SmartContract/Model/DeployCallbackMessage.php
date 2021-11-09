<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class DeployCallbackMessage
{
    private string $tokenName;
    private string $address;
    private string $txHash;

    private function __construct(
        string $tokenName,
        string $address,
        string $txHash
    ) {
        $this->tokenName = $tokenName;
        $this->address = $address;
        $this->txHash = $txHash;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getTxHash(): string
    {
        return $this->txHash;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['tokenName'],
            $data['address'],
            $data['txHash']
        );
    }

    public function toArray(): array
    {
        return [
            'tokenName' => $this->getTokenName(),
            'address' => $this->getAddress(),
            'txHash' => $this->txHash,
        ];
    }
}
