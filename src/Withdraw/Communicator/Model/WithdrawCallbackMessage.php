<?php

namespace App\Withdraw\Communicator\Model;

class WithdrawCallbackMessage
{
    /** @var int */
    private $id;

    /** @var string */
    private $status;

    /** @var string */
    private $transactionHash;

    /** @var string */
    private $transactionKey;

    /** @var int */
    private $retries;

    private function __construct(
        int $id,
        string $status,
        string $transactionHash,
        string $transactionKey,
        int $retries
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->transactionHash = $transactionHash;
        $this->transactionKey = $transactionKey;
        $this->retries = $retries;
    }

    public function getPaymentId(): int
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

    public function getTransactionKey(): string
    {
        return $this->transactionKey;
    }

    public function getRetriesCount(): int
    {
        return $this->retries;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['id'],
            $data['status'],
            $data['tx_hash'],
            $data['tx_key'],
            $data['retries'] ?? 0
        );
    }

    public function getMessageWithIncrementedRetryCount(): self
    {
        return new self(
            $this->id,
            $this->status,
            $this->transactionHash,
            $this->transactionKey,
            $this->retries + 1
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getPaymentId(),
            'status' => $this->getStatus(),
            'tx_hash' => $this->getTransactionHash(),
            'tx_key' => $this->getTransactionKey(),
            'retries' => $this->getRetriesCount(),
        ];
    }
}
