<?php

namespace App\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;

class PasswordGeneratorButtonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('button', ButtonType::class, [
                'label' => 'Generate new password',
                'attr' => [
                    'class' => 'btn btn-primary btn-sm password-generator-button',
                ],
            ]);
    }
}
