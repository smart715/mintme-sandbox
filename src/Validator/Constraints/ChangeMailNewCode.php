<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation  */
class ChangeMailNewCode extends Constraint
{
    public string $message = '{{message}}'; //phpcs:ignore
}
