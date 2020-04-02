<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UserEmailSymbols extends Constraint
{
    /** @var string */
    public $message = "Email contains invalid symbol";
}
