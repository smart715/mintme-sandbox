<?php declare(strict_types = 1);

namespace App\Manager\Model;

/** @codeCoverageIgnore */
class FinanceBalanceModel
{
    private string $timestamp;
    private string $crypto;
    private string $blockChainBalance;
    private string $usersBalance;
    private string $difference;
    private string $withdrawFeeToPay;
    private string $differenceFeeColdWallet;
    private string $coldWalletBalance;

    public function __construct(
        string $timestamp,
        string $crypto,
        string $blockChainBalance,
        string $usersBalance,
        string $difference,
        string $withdrawFeeToPay,
        string $differenceFeeColdWallet,
        string $coldWalletBalance
    ) {
        $this->timestamp = $timestamp;
        $this->crypto = $crypto;
        $this->blockChainBalance = $blockChainBalance;
        $this->usersBalance = $usersBalance;
        $this->difference = $difference;
        $this->withdrawFeeToPay = $withdrawFeeToPay;
        $this->differenceFeeColdWallet = $differenceFeeColdWallet;
        $this->coldWalletBalance = $coldWalletBalance;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getCrypto(): string
    {
        return $this->crypto;
    }

    public function getBlockChainBalance(): string
    {
        return $this->blockChainBalance;
    }

    public function getUsersBalance(): string
    {
        return $this->usersBalance;
    }

    public function getDifference(): string
    {
        return $this->difference;
    }

    public function getWithdrawFeeToPay(): string
    {
        return $this->withdrawFeeToPay;
    }

    public function getDifferenceFeeColdWallet(): string
    {
        return $this->differenceFeeColdWallet;
    }

    public function getColdWalletBalance(): string
    {
        return $this->coldWalletBalance;
    }
}
