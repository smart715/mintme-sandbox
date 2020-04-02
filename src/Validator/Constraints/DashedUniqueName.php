<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class DashedUniqueName extends Constraint
{
    /** @var string */
    public $message = 'Token name is already exists.';
}
