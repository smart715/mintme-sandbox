<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class UpdateMintedAmountCallbackMessage
{
    private string $tokenName;
    private string $cryptoSymbol;
    private string $value;

    private function __construct(
        string $tokenName,
        string $cryptoSymbol,
        string $value
    ) {
        $this->tokenName = $tokenName;
        $this->cryptoSymbol = $cryptoSymbol;
        $this->value = $value;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function getCryptoSymbol(): string
    {
        return $this->cryptoSymbol;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['token'],
            $data['crypto'],
            $data['value']
        );
    }

    public function toArray(): array
    {
        return [
            'token' => $this->getTokenName(),
            'crypto' => $this->getCryptoSymbol(),
            'value' => $this->getValue(),
        ];
    }
}
