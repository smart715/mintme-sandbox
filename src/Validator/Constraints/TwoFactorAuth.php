<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TwoFactorAuth extends Constraint
{
    /** @var string */
    public $message = 'Invalid two-factor authentication code.';
}
