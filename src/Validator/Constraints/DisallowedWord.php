<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class DisallowedWord extends Constraint
{
    /** @var string */
    public $message = 'Token and coin words are not permitted. Those can be used only concatenated';
}
