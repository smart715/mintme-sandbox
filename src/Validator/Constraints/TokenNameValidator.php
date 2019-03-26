<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\TokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TokenNameValidator extends ConstraintValidator
{
    /** @var mixed */
    private $token;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(TokenManagerInterface $tokenManager, TokenStorageInterface $token)
    {
        $this->user = $token->getToken()->getUser();
        $this->tokenManager = $tokenManager;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        $user = $this->userManager->findUserByEmail($value ?? '');

        if (!is_null($user) && ($this->user !== $user || $value === $user->getEmail())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
