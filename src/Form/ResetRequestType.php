<?php

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

class ResetRequestType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', EmailType::class, [
                'label' => 'Email',
                'constraints' => [new Email(['message' => 'Invalid email address.', 'checkMX' => true]) ],
            ])
        
            ->add('recaptcha', EWZRecaptchaType::class, [
                'attr' => [
                    'options' => [
                        'theme' => 'dark',
                        'size' => 'normal',
                        'type'  => 'image',
                    ],
                ],
                'mapped' => false,
                'constraints' => [ new RecaptchaTrue() ],
                'label' => false,
                'error_bubbling' => true,
            ])
        ;
    }
    
    public function getBlockPrefix(): string
    {
        return '';
    }
}
