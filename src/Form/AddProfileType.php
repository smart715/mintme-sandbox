<?php

namespace App\Form;

use App\Form\Model\EmailModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddProfileType extends AbstractType
{
    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                    'pattern' => '[A-Za-z\s-]+',
                    'title' => 'not valid name',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                    'pattern' => '[A-Za-z\s-]+',
                    'title' => 'not valid name',
                ],
            ]);
    }
}
