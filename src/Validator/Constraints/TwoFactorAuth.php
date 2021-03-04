<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class TwoFactorAuth extends Constraint
{
    /** @var string */
    public $message = '2fa.invalid';
}
