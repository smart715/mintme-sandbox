<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Blacklist\Blacklist;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\EditPhoneNumber;
use App\Validator\Constraints\IsNotBlacklisted;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/** @codeCoverageIgnore */
class PhoneNumberType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('phoneNumber', \Misd\PhoneNumberBundle\Form\Type\PhoneNumberType::class, [
            'format' => PhoneNumberFormat::E164,
            'label' => $this->translator->trans('page.profile.form.phone_number'),
            'constraints' => [
                new PhoneNumber(['type' => ['mobile','fixed_line']]),
                new EditPhoneNumber(),
                new IsNotBlacklisted([
                    'type' => Blacklist::PHONE,
                    'message' => $this->translator->trans('phone_number.in_use'),
                ]),
            ],
            'attr' => [
                'class' => 'd-none',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
