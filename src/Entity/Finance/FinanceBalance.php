<?php declare(strict_types = 1);

namespace App\Entity\Finance;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Finance\FinanceBalanceRepository")
 * @ORM\Table(name="finance_balance")
 * @codeCoverageIgnore
 */
class FinanceBalance
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true, options={"default": "CURRENT_TIMESTAMP"})
     */
    protected \DateTimeImmutable $timestamp; // phpcs:ignore

    /**
     * @ORM\Column(type="float", nullable=false, )
     */
    protected float $blockchainBalance = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected string $crypto;

    /**
     * @ORM\Column(type="float", nullable=false)
     */
    protected float $usersBalance = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected float $fee = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected float $feePaid = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected float $withdrawFeeToPay = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected float $botBalance = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected float $coldWalletBalance = 0; // phpcs:ignore

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getBlockchainBalance(): float
    {
        return $this->blockchainBalance;
    }

    public function setBlockchainBalance(float $blockchainBalance): self
    {
        $this->blockchainBalance = $blockchainBalance;

        return $this;
    }

    public function getUsersBalance(): float
    {
        return $this->usersBalance;
    }

    public function setUsersBalance(float $usersBalance): self
    {
        $this->usersBalance = $usersBalance;

        return $this;
    }

    public function getFee(): float
    {
        return $this->fee;
    }

    public function setFee(float $fee): self
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFeePaid(): float
    {
        return $this->feePaid;
    }

    public function setFeePaid(float $feePaid): self
    {
        $this->feePaid = $feePaid;

        return $this;
    }

    public function getWithdrawFeeToPay(): float
    {
        return $this->withdrawFeeToPay;
    }

    public function setWithdrawFeeToPay(float $withdrawFeeToPay): self
    {
        $this->withdrawFeeToPay = $withdrawFeeToPay;

        return $this;
    }

    public function getBotBalance(): float
    {
        return $this->botBalance;
    }

    public function setBotBalance(float $botBalance): self
    {
        $this->botBalance = $botBalance;

        return $this;
    }

    public function getColdWalletBalance(): float
    {
        return $this->coldWalletBalance;
    }

    public function setColdWalletBalance(float $coldWalletBalance): self
    {
        $this->coldWalletBalance = $coldWalletBalance;

        return $this;
    }

    public function getCrypto(): string
    {
        return $this->crypto;
    }

    public function setCrypto(string $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    public function getDifference(): float
    {
        return $this->getBlockchainBalance() - $this->getUsersBalance();
    }

    public function getDifferenceInclFee(): float
    {
        return $this->getBlockchainBalance() -
            $this->getUsersBalance() +
            $this->getWithdrawFeeToPay() +
            $this->getColdWalletBalance();
    }
}
