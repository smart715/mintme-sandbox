<?php

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CaptchaLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recaptcha', EWZRecaptchaType::class, [
                'mapped' => false,
                'constraints' => [ new RecaptchaTrue() ],
                'label' => false,
                'error_bubbling' => true,
            ])
        ;
    }
}
