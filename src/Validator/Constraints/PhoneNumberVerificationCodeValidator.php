<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneNumberVerificationCodeValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private User $user;

    public function __construct(TranslatorInterface $translator, TokenStorageInterface $tokenStorage)
    {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $tokenStorage->getToken()->getUser();
        $this->user = $user;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     *
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        $verificationCode = $this->user->getProfile()->getPhoneNumber()->getVerificationCode();

        if (!$verificationCode || $value !== $verificationCode) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans('phone_confirmation.invalid_code'))
                ->addViolation();
        }
    }
}
