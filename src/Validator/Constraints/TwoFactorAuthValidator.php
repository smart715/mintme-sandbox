<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Manager\TwoFactorManager;
use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TwoFactorAuthValidator extends ConstraintValidator
{
    /** @var User */
    public $user;
    protected TwoFactorManager $twoFactorManager;
    private TranslatorInterface $translator;

    public function __construct(
        TokenStorageInterface $token,
        TwoFactorManager $twoFactorManager,
        TranslatorInterface $translator
    ) {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $token->getToken()->getUser();
        $this->user = $user;
        $this->twoFactorManager = $twoFactorManager;
        $this->translator = $translator;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        if (!$value || !$this->twoFactorManager->checkCode($this->user, $value)) {
            $this->context->buildViolation($this->translator->trans($constraint->message))->addViolation();
        }
    }
}
