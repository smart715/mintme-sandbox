<?php declare(strict_types = 1);

namespace App\Wallet\Model;

/** @codeCoverageIgnore */
class MainBalanceCallbackMessage
{
    private int $userId;
    private string $amount; // amount to withdraw, or amount to forward deposit
    private string $type; // Type::DEPOSIT or Type::WITHDRAWAL

    private string $crypto;
    private string $cryptoBalance;
    private string $cryptoNeed;

    // if token is present, it's a token transaction
    private ?string $token; // if token is present, all toke related fields should be present too
    private ?string $tokenBalance;
    private ?string $tokenNeed;

    private function __construct(
        int $userId,
        string $amount,
        string $type,
        string $crypto,
        string $cryptoBalance,
        string $cryptoNeed,
        ?string $token,
        ?string $tokenBalance,
        ?string $tokenNeed
    ) {
        if ($token && (!isset($tokenBalance) || !isset($tokenNeed))) {
            throw new \Exception('Both token and balance should be present or none');
        }

        $this->userId = $userId;
        $this->amount = $amount;
        $this->type = $type;

        $this->crypto = $crypto;
        $this->cryptoBalance = $cryptoBalance;
        $this->cryptoNeed = $cryptoNeed;

        $this->token = $token;
        $this->tokenBalance = $tokenBalance;
        $this->tokenNeed = $tokenNeed;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCrypto(): string
    {
        return $this->crypto;
    }

    public function getCryptoBalance(): string
    {
        return $this->cryptoBalance;
    }

    public function getCryptoNeed(): string
    {
        return $this->cryptoNeed;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getTokenBalance(): ?string
    {
        return $this->tokenBalance;
    }

    public function getTokenNeed(): ?string
    {
        return $this->tokenNeed;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['userId'],
            $data['amount'],
            $data['type'],
            $data['crypto'],
            $data['cryptoBalance'],
            $data['cryptoNeed'],
            $data['token'] ?? null,
            $data['tokenBalance'] ?? null,
            $data['tokenNeed'] ?? null,
        );
    }
}
