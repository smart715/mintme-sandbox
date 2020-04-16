<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class PositiveAmount extends Constraint
{
    /** @var string */
    public $message = 'Amount must be positive';
}
