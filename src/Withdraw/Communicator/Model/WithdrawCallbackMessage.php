<?php declare(strict_types = 1);

namespace App\Withdraw\Communicator\Model;

class WithdrawCallbackMessage
{
    /** @var int */
    private $id;

    /** @var string */
    private $status;

    /** @var string */
    private $transactionHash;

    /** @var int */
    private $retries;

    /** @var string */
    private $crypto;

    /** @var string */
    private $amount;

    private function __construct(
        int $id,
        string $status,
        string $transactionHash,
        int $retries,
        string $crypto,
        string $amount
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->transactionHash = $transactionHash;
        $this->retries = $retries;
        $this->crypto = $crypto;
        $this->amount = $amount;
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

    public static function parse(array $data): self
    {
        return new self(
            $data['id'],
            $data['status'],
            $data['tx_hash'],
            $data['retries'] ?? 0,
            $data['crypto'],
            $data['amount']
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
            $this->amount
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
        ];
    }
}
