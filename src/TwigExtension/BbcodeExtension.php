<?php declare(strict_types = 1);

namespace App\TwigExtension;

use JBBCode\DefaultCodeDefinitionSet;
use JBBCode\Parser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BbcodeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('bbcode', [$this, 'bbcode']),
        ];
    }

    public function bbcode(?string $value): ?string
    {
        $parser = new Parser();
        $parser->addCodeDefinitionSet(new DefaultCodeDefinitionSet());

        return $parser->parse($value ?? '')->getAsHTML();
    }
}
