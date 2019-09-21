<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class DeployCallbackMessage
{
    /** @var string */
    private $tokenName;

    /** @var string */
    private $address;

    private function __construct(
        string $tokenName,
        string $address
    ) {
        $this->tokenName = $tokenName;
        $this->address = $address;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function getAddress(): string
    {
        return $this->address;
    }


    public static function parse(array $data): self
    {
        return new self(
            $data['tokenName'],
            $data['address']
        );
    }

    public function toArray(): array
    {
        return [
            'tokenName' => $this->getTokenName(),
            'address' => $this->getAddress(),
        ];
    }
}
