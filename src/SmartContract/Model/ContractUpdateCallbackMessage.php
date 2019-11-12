<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class ContractUpdateCallbackMessage
{
    /** @var string */
    private $tokenAddress;

    /** @var string */
    private $mintDestination;

    /** @var bool */
    private $lock;

    private function __construct(
        string $tokenAddress,
        string $mintDestination,
        bool $lock
    ) {
        $this->tokenAddress = $tokenAddress;
        $this->mintDestination = $mintDestination;
        $this->lock = $lock;
    }

    public function getTokenAddress(): string
    {
        return $this->tokenAddress;
    }

    public function getMintDestination(): string
    {
        return $this->mintDestination;
    }

    public function getLock(): bool
    {
        return $this->lock;
    }


    public static function parse(array $data): self
    {
        return new self(
            $data['tokenAddress'],
            $data['mintDestination'],
            $data['lock']
        );
    }

    public function toArray(): array
    {
        return [
            'tokenAddress' => $this->getTokenAddress(),
            'mintDestination' => $this->getMintDestination(),
            'lock' => $this->getLock(),
        ];
    }
}
