<?php declare(strict_types = 1);

namespace App\Wallet\Deposit\Model;

/** @codeCoverageIgnore */
class ValidDeposit
{
    private DepositCallbackMessage $clbResult;
    private bool $status;
    private ?string $errorMessage;

    private function __construct(
        DepositCallbackMessage $clbResult,
        bool $status,
        ?string $errorMessage
    ) {
        $this->clbResult = $clbResult;
        $this->status = $status;
        $this->errorMessage = $errorMessage;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getDepositMessage(): DepositCallbackMessage
    {
        return $this->clbResult;
    }

    public static function parse(DepositCallbackMessage $clbResult, array $data): self
    {
        return new self(
            $clbResult,
            $data['status'],
            $data['error'] ?? null,
        );
    }
}
