<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\PhoneNumberManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditPhoneNumberValidator extends ConstraintValidator
{
    private ParameterBagInterface $parameterBag;
    private PhoneNumberManagerInterface $phoneNumberManager;
    private TranslatorInterface $translator;

    public function __construct(
        ParameterBagInterface $parameterBag,
        PhoneNumberManagerInterface $phoneNumberManager,
        TranslatorInterface $translator
    ) {
        $this->parameterBag = $parameterBag;
        $this->phoneNumberManager = $phoneNumberManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     *
     * @param EditPhoneNumber $constraint
     * @throws \Exception
     */
    public function validate($value, Constraint $constraint): void
    {
        $phoneNumber = $this->phoneNumberManager->findByPhoneNumber($value);

        if (!$phoneNumber || !$phoneNumber->getEditDate()) {
            return;
        }

        $editDate = $phoneNumber->getEditDate();
        $possibleEditDate = $editDate->add(
            new \DateInterval('P'.$this->parameterBag->get('edit_phone')['interval'])
        );

        if ($possibleEditDate > $editDate) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans('phone_number.edit.limit'))
                ->addViolation();
        }
    }
}
