<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class TokenDescription extends Constraint
{
    /** @var int */
    public $min;

    /** @var int */
    public $max;

    public string $message = 'Description must be more than {{min}} and less than {{max}}.'; // phpcs:ignore

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return [
            'min',
            'max',
        ];
    }
}
