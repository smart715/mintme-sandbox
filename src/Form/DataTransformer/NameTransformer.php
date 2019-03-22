<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NameTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return $this->removeDoublespaces($value);
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return $this->removeDoublespaces($value);
    }

    private function removeDoublespaces(?string $text): ?string
    {
        if ($text) {
            return preg_replace('/\s+/', ' ', $text);
        }
        return null;
    }
}
