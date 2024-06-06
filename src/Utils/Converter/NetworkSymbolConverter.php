<?php declare(strict_types = 1);

namespace App\Utils\Converter;

class NetworkSymbolConverter implements NetworkSymbolConverterInterface
{
    private array $conversionMap;

    public function __construct(array $conversionMap)
    {
        $this->conversionMap = $conversionMap;
    }

    public function convert(string $name): ?string
    {
        $name = strtoupper($name);

        return strtr($name, $this->conversionMap);
    }
}
