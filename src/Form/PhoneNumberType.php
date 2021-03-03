<?php declare(strict_types = 1);

namespace App\Form;

use App\Validator\Constraints\EditPhoneNumber;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @codeCoverageIgnore  */
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
                new PhoneNumber(['type' => 'mobile']),
                new EditPhoneNumber(),
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
