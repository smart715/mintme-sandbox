<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class TokenSignupBonusCode extends Constraint
{
    /** @var string */
    public $message = 'token.sign_up_bonus.campaign_end'; // phpcs:ignore
}
