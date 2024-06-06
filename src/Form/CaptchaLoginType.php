<?php declare(strict_types = 1);

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/** @codeCoverageIgnore */
class CaptchaLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recaptcha', EWZRecaptchaType::class, [
                'attr' => [
                    'options' => [
                        'theme' => 'white',
                        'size' => 'normal',
                        'type'  => 'image',
                        'injectScript' => false,
                        'defer' => true,
                    ],
                ],
                'mapped' => false,
                'label' => false,
                'error_bubbling' => true,
            ])
        ;
    }
}
