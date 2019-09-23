<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class ContractUpdateCallbackMessage
{
    /** @var string */
    private $tokenAddress;

    /** @var string */
    private $minDestination;

    private function __construct(
        string $tokenAddress,
        string $minDestination
    ) {
        $this->tokenAddress = $tokenAddress;
        $this->minDestination = $minDestination;
    }

    public function getTokenAddress(): string
    {
        return $this->tokenAddress;
    }

    public function getMinDestination(): string
    {
        return $this->minDestination;
    }


    public static function parse(array $data): self
    {
        return new self(
            $data['tokenAddress'],
            $data['minDestination']
        );
    }

    public function toArray(): array
    {
        return [
            'tokenAddress' => $this->getTokenAddress(),
            'minDestination' => $this->getMinDestination(),
        ];
    }
}
