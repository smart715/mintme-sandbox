<?php declare(strict_types = 1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore  */
class ResettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->remove('plainPassword')
            ->add('plainPassword', PasswordType::class, [
                'label' => 'New password:',
                'translation_domain' => 'FOSUserBundle',
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ])
        ;
    }

    public function getParent(): string
    {
        return 'FOS\UserBundle\Form\Type\ResettingFormType';
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 'app_user_resetting';
    }
}
