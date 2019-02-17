<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Manager\TwoFactorManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TwoFactorAuthValidator extends ConstraintValidator
{
    /** @var User */
    protected $user;

    /** @var TwoFactorManager  */
    protected $twoFactorManager;

    public function __construct(TokenStorageInterface $token, TwoFactorManager $twoFactorManager)
    {
        $this->user = $token->getToken()->getUser();
        $this->twoFactorManager = $twoFactorManager;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        if ($value && !$this->twoFactorManager->checkCode($this->user, $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
