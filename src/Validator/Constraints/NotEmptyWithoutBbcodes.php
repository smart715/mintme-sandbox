<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class NotEmptyWithoutBbcodes extends Constraint
{
    /** @var string */
    public $message = 'Content must not be empty or made up of only bbcodes';
}
