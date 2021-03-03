<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\PhoneNumberManagerInterface;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddPhoneNumberValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private ParameterBagInterface $parameterBag;
    private PhoneNumberUtil $phoneNumberUtil;
    private PhoneNumberManagerInterface $phoneNumberManager;

    public function __construct(
        TranslatorInterface $translator,
        ParameterBagInterface $parameterBag,
        PhoneNumberUtil $phoneNumberUtil,
        PhoneNumberManagerInterface $phoneNumberManager
    ) {
        $this->translator = $translator;
        $this->parameterBag = $parameterBag;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->phoneNumberManager = $phoneNumberManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint AddPhoneNumber
     */
    public function validate($value, Constraint $constraint): void
    {
        $limits = $this->parameterBag->get('adding_phone_attempts_limit');
        $daily = $limits['daily'];
        $weekly = $limits['weekly'];
        $monthly = $limits['monthly'];

        $phoneNumber = $this->phoneNumberManager->findByPhoneNumber($value);

        if (!$phoneNumber) {
            return;
        }

        if ($phoneNumber->getDailyAttempts() >= $daily) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans('phone_number.add.limit_daily'))
                ->addViolation();
        } elseif ($phoneNumber->getWeeklyAttempts() >= $weekly) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans('phone_number.add.limit_weekly'))
                ->addViolation();
        } elseif ($phoneNumber->getMonthlyAttempts() >= $monthly) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans('phone_number.add.limit_monthly'))
                ->addViolation();
        }
    }
}
