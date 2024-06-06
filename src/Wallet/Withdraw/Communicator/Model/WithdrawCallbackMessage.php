<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw\Communicator\Model;

/** @codeCoverageIgnore */
class WithdrawCallbackMessage
{
    private int $id;
    private string $status;
    private string $transactionHash;
    private int $retries;
    private string $crypto;
    private string $amount;
    private string $address;
    private string $cryptoNetwork;

    private function __construct(
        int $id,
        string $status,
        string $transactionHash,
        int $retries,
        string $crypto,
        string $amount,
        string $address,
        string $cryptoNetwork
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->transactionHash = $transactionHash;
        $this->retries = $retries;
        $this->crypto = $crypto;
        $this->amount = $amount;
        $this->address = $address;
        $this->cryptoNetwork = $cryptoNetwork;
    }

    public function getUserId(): int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }

    public function getRetriesCount(): int
    {
        return $this->retries;
    }

    public function getCrypto(): string
    {
        return $this->crypto;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCryptoNetwork(): string
    {
        return $this->cryptoNetwork;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['id'],
            $data['status'],
            $data['tx_hash'],
            $data['retries'] ?? 0,
            $data['crypto'],
            $data['amount'],
            $data['address'] ?? '',
            $data['cryptoNetwork'] ?? ''
        );
    }

    public function getMessageWithIncrementedRetryCount(): self
    {
        return new self(
            $this->id,
            $this->status,
            $this->transactionHash,
            $this->retries + 1,
            $this->crypto,
            $this->amount,
            $this->address,
            $this->cryptoNetwork
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getUserId(),
            'status' => $this->getStatus(),
            'tx_hash' => $this->getTransactionHash(),
            'retries' => $this->getRetriesCount(),
            'crypto' => $this->getCrypto(),
            'amount' => $this->getAmount(),
            'address' => $this->getAddress(),
            'cryptoNetwork' => $this->getCryptoNetwork(),
        ];
    }
}
