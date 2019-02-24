<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NameTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return $this->removeMultispaces($value);
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return $this->removeMultispaces($value);
    }

    private function removeMultispaces(?string $text): ?string
    {
        if ($text) {
            return preg_replace('/\s+/', ' ', $text);
        }
    }
}
