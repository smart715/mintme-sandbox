<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class UpdateMintedAmountCallbackMessage
{
    /** @var string */
    private $tokenName;

    /** @var string */
    private $value;

    private function __construct(
        string $tokenName,
        string $value
    ) {
        $this->tokenName = $tokenName;
        $this->value = $value;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['token'],
            $data['value']
        );
    }

    public function toArray(): array
    {
        return [
            'token' => $this->getTokenName(),
            'value' => $this->getValue(),
        ];
    }
}
