<?php

namespace App\Form;

use App\Form\Model\EmailModel;
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
            ->add('firstname', TextType::class, [ 'label' => 'First Name' ])
            ->add('lastname', TextType::class, [ 'label' => 'Last Name' ])
            ->add('city', TextType::class, [ 'label' => 'City' ])
            ->add('country', CountryType::class, [ 'label' => 'Country' ])
            ->add('description', TextareaType::class, [ 'label' => 'Description']);
    }
}
