<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use Symfony\Component\Form\DataTransformerInterface;

class NameTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return (new StringConverter(new ParseStringStrategy()))->convert($value);
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return (new StringConverter(new ParseStringStrategy()))->convert($value);
    }
}
