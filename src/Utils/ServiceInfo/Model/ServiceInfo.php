<?php declare(strict_types = 1);

namespace App\Utils\ServiceInfo\Model;

use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class ServiceInfo
{
    /**
     * @Groups({"API"})
     */
    private ?string  $tokenName;

    /**
     * @Groups({"API"})
     */
    private ?string $panelBranch;

    /**
     * @Groups({"API"})
     */
    private ?string $gatewayBranch;

    /**
     * @Groups({"API"})
     */
    private array $consumersInfo;

    /**
     * @Groups({"API"})
     */
    private bool $isGatewayActive;

    public function getTokenName(): ?string
    {
        return $this->tokenName;
    }

    public function setTokenName(?string $tokenName): self
    {
        $this->tokenName = $tokenName;

        return $this;
    }

    public function getGatewayBranch(): ?string
    {
        return $this->gatewayBranch;
    }

    public function setGatewayBranch(?string $gatewayBranch): self
    {
        $this->gatewayBranch = $gatewayBranch;

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

    public function getConsumersInfo(): array
    {
        return $this->consumersInfo;
    }

    public function setConsumersInfo(array $consumersInfo): self
    {
        $this->consumersInfo = $consumersInfo;

        return $this;
    }

    public function isGatewayActive(): bool
    {
        return $this->isGatewayActive;
    }

    public function setIsGatewayActive(bool $isActive): self
    {
        $this->isGatewayActive = $isActive;

        return $this;
    }
}
