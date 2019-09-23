<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class ContractUpdateCallbackMessage
{
    /** @var string */
    private $tokenAddress;

    /** @var string */
    private $minDestination;

    /** @var bool */
    private $lock;

    private function __construct(
        string $tokenAddress,
        string $minDestination,
        bool $lock
    ) {
        $this->tokenAddress = $tokenAddress;
        $this->minDestination = $minDestination;
        $this->lock = $lock;
    }

    public function getTokenAddress(): string
    {
        return $this->tokenAddress;
    }

    public function getMinDestination(): string
    {
        return $this->minDestination;
    }

    public function getLock(): bool
    {
        return $this->lock;
    }


    public static function parse(array $data): self
    {
        return new self(
            $data['tokenAddress'],
            $data['minDestination'],
            $data['lock']
        );
    }

    public function toArray(): array
    {
        return [
            'tokenAddress' => $this->getTokenAddress(),
            'minDestination' => $this->getMinDestination(),
            'lock' => $this->getLock(),
        ];
    }
}
