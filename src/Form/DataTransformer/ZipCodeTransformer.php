<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use Symfony\Component\Form\DataTransformerInterface;

class ZipCodeTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return mb_strtoupper($value ?? '');
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return mb_strtoupper($value ?? '');
    }
}
