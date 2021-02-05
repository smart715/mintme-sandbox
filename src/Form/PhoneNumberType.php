<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\PhoneNumber;
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
            'format' => PhoneNumberFormat::NATIONAL,
            'label' => $this->translator->trans('page.profile.form.phone_number'),
            'attr' => [
                'class' => 'd-none',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PhoneNumber::class,
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
