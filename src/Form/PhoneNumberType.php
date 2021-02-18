<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\PhoneNumber;
use App\Validator\Constraints\EditPhoneNumber;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
                new \Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber(['type' => 'mobile']),
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
