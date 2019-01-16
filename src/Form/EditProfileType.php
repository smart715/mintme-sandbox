<?php

namespace App\Form;

use App\Entity\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'City',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                    'pattern' => '[^\d]+',
                    'title' => 'shouldn\'t contain numbers',
                ],
            ])
            ->add('country', CountryType::class, [ 'label' => 'Country' ])
            ->add('description', TextareaType::class, [ 'label' => 'Description', 'attr' => [ 'maxlength' => 150 ] ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Profile::class);
    }
}
