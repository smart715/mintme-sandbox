<?php

namespace App\Form;

use App\Entity\User;
use FOS\UserBundle\Form\Type\ResettingFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', User::class);
    }

    /** {@inheritdoc} */
    public function getParent()
    {
        return ResettingFormType::class;
    }
}
