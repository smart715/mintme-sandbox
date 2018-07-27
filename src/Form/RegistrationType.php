<?php

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->remove('username')
            ->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => [ new RecaptchaTrue() ],
                'label' => false,
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
