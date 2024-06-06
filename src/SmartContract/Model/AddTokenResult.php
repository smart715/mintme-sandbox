<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class AddTokenResult
{
    private string $name;
    private int $decimals;
    private bool $existed;
    private bool $isPausable;

    private function __construct(
        string $name,
        int $decimals,
        bool $existed,
        bool $isPausable
    ) {
        $this->name = $name;
        $this->decimals = $decimals;
        $this->existed = $existed;
        $this->isPausable = $isPausable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function alreadyExisted(): bool
    {
        return $this->existed;
    }

    public function isPausable(): bool
    {
        return $this->isPausable;
    }

    public static function parse(array $data): self
    {
        return new self(
            $data['name'],
            (int)$data['decimals'],
            $data['existed'],
            $data['isPausable'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'decimals' => $this->getDecimals(),
            'existed' => $this->alreadyExisted(),
            'isPausable' => $this->isPausable(),
        ];
    }
}
