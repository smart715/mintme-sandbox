<?php declare(strict_types = 1);

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore  */
class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->remove('username')
            ->remove('plainPassword')
            ->add('recaptcha', EWZRecaptchaType::class, [
                'attr' => [
                    'options' => [
                        'theme' => 'dark',
                        'size' => 'normal',
                        'type'  => 'image',
                        'injectScript' => false,
                    ],
                ],
                'mapped' => false,
                'constraints' => [ new RecaptchaTrue() ],
                'label' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password:',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            "allow_extra_fields" => true
        ));
    }

    public function getParent(): string
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix(): string
    {
        return 'app_user_registration';
    }
}
