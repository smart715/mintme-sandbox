<?php

namespace App\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResetRequestType extends AbstractType
{
    /**
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Invalid email address.",
     *     checkMX = true
     * )
     * @var string
     */
     protected $username;
     
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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

    public function setUsername($username)
    {
        $this->username = $username;
        return parent::setUsername($username);
    }
}
