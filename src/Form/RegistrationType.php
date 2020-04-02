<?php declare(strict_types = 1);

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

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

    public function getParent(): string
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix(): string
    {
        return 'app_user_registration';
    }
}
