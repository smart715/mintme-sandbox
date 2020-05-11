<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Manager\TwoFactorManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TwoFactorAuthValidator extends ConstraintValidator
{
    /** @var User */
    public $user;

    /** @var TwoFactorManager  */
    protected $twoFactorManager;

    public function __construct(TokenStorageInterface $token, TwoFactorManager $twoFactorManager)
    {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $token->getToken()->getUser();
        $this->user = $user;
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
