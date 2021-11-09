<?php declare(strict_types = 1);

namespace App\Utils\ServiceInfo\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class ServiceInfo
{
    /**
     * @var string|null
     * @Groups({"API"})
     */
    private $tokenName;

    /**
     * @var string|null
     * @Groups({"API"})
     */
    private $panelBranch;

    /**
     * @var string|null
     * @Groups({"API"})
     */
    private $depositBranch;

    /**
     * @var string|null
     * @Groups({"API"})
     */
    private $contractBranch;

    /**
     * @var string|null
     * @Groups({"API"})
     */
    private $withdrawBranch;

    /**
     * @var array<string>
     * @Groups({"API"})
     */
    private $consumersInfo;

    /**
     * @var bool
     * @Groups({"API"})
     */
    private $isTokenContractActive;

    public function getTokenName(): ?string
    {
        return $this->tokenName;
    }

    public function setTokenName(?string $tokenName): self
    {
        $this->tokenName = $tokenName;

        return $this;
    }

    public function getDepositBranch(): ?string
    {
        return $this->depositBranch;
    }

    public function setDepositBranch(?string $depositBranch): self
    {
        $this->depositBranch = $depositBranch;

        return $this;
    }

    public function getPanelBranch(): ?string
    {
        return $this->panelBranch;
    }

    public function setPanelBranch(?string $panelBranch): self
    {
        $this->panelBranch = $panelBranch;

        return $this;
    }

    public function getContractBranch(): ?string
    {
        return $this->contractBranch;
    }

    public function setContractBranch(?string $contractBranch): void
    {
        $this->contractBranch = $contractBranch;
    }

    public function getWithdrawBranch(): ?string
    {
        return $this->withdrawBranch;
    }

    public function setWithdrawBranch(?string $withdrawBranch): self
    {
        $this->withdrawBranch = $withdrawBranch;

        return $this;
    }

    public function getConsumersInfo(): array
    {
        return $this->consumersInfo;
    }

    public function setConsumersInfo(array $consumersInfo): self
    {
        $this->consumersInfo = $consumersInfo;

        return $this;
    }

    public function isTokenContractActive(): bool
    {
        return $this->isTokenContractActive;
    }

    public function setIsTokenContractActive(bool $isActive): self
    {
        $this->isTokenContractActive = $isActive;

        return $this;
    }
}
