<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class DeployCallbackMessage
{
    public const STATUS_SUCCESS = 'ok';
    public const STATUS_FAILURE = 'fail';

    private string $tokenName;
    private string $crypto;
    private string $address;
    private string $txHash;
    private string $status;

    private function __construct(
        string $tokenName,
        string $crypto,
        string $address,
        string $txHash,
        string $status
    ) {
        $this->tokenName = $tokenName;
        $this->crypto = $crypto;
        $this->address = $address;
        $this->txHash = $txHash;
        $this->status = $status;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function getCrypto(): string
    {
        return $this->crypto;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getTxHash(): string
    {
        return $this->txHash;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['tokenName'],
            $data['crypto'],
            $data['address'],
            $data['txHash'],
            $data['status']
        );
    }

    public function toArray(): array
    {
        return [
            'tokenName' => $this->getTokenName(),
            'crypto' => $this->getCrypto(),
            'address' => $this->getAddress(),
            'txHash' => $this->getTxHash(),
            'status' => $this->getStatus(),
        ];
    }
}
