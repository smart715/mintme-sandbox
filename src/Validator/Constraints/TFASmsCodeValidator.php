<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Manager\TwoFactorManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TFASmsCodeValidator extends ConstraintValidator
{
    
    private User $user;
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
        $code = $this->twoFactorManager
            ->getGoogleAuthEntry($this->user->getId())
            ->getSMSCode()
            ->getCode();

        if (!$code || $value !== $code) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans(
                    '2fa.backup_codes.download.invalid_code',
                    ['%code%' => 'SMS'],
                ))
                ->addViolation();
        }
    }
}
