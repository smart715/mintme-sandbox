<?php

namespace App\Form;

use App\Entity\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [ 'label' => 'First name:' ])
            ->add('lastName', TextType::class, [ 'label' => 'Last name:' ])
            ->add('city', TextType::class, [ 'label' => 'City:', 'required' => false ])
            ->add('country', CountryType::class, [ 'label' => 'Country:', 'required' => false ])
            ->add('description', TextareaType::class, [ 'label' => 'Description:', 'required' => false ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Profile::class);
    }
}
