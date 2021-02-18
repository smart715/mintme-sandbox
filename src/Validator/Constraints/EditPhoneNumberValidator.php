<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditPhoneNumberValidator extends ConstraintValidator
{
    private ParameterBagInterface $parameterBag;
    private TranslatorInterface $translator;
    private User $user;
    private PhoneNumberUtil $numberUtil;

    public function __construct(
        ParameterBagInterface $parameterBag,
        TranslatorInterface $translator,
        TokenStorageInterface $token,
        PhoneNumberUtil $numberUtil
    ) {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $token->getToken()->getUser();
        $this->user = $user;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->numberUtil = $numberUtil;
    }

    /**
     * {@inheritDoc}
     *
     * @param EditPhoneNumber $constraint
     * @throws \Exception
     */
    public function validate($value, Constraint $constraint): void
    {
        $oldPhoneNumber = $this->user->getProfile()->getPhoneNumber();

        if (!$oldPhoneNumber ||
            !$oldPhoneNumber->getEditDate() ||
            $this->numberUtil->format($value, PhoneNumberFormat::E164) ===
            $this->numberUtil->format($oldPhoneNumber->getPhoneNumber(), PhoneNumberFormat::E164)
        ) {
            return;
        }

        $editDate = $oldPhoneNumber->getEditDate();
        $possibleEditDate = $editDate->add(
            new \DateInterval('P'.$this->parameterBag->get('edit_phone')['interval'])
        );

        if ($possibleEditDate > $editDate ||
            $oldPhoneNumber->getEditAttempts() >= (int)$this->parameterBag->get('edit_phone')['attempts']
        ) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans('phone_number.edit.limit'))
                ->addViolation();
        }
    }
}
