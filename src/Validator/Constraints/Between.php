<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class Between extends Constraint
{
    /** @var string */
    public $min;

    /** @var string */
    public $max;

    /** @var string */
    public $message = 'Amount must be more than {{min}} and less than {{max}}.';

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
