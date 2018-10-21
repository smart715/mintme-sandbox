<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class GreaterThanPrevious extends Constraint
{
    /** @var string */
    public $message = 'This value must be greater than previous one';
}
