<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UserPassword extends Constraint
{
    /** @var string */
    public $message = "The password must contain minimum eight symbols, at least one uppercase letter, a lowercase letter, and a number";
}
