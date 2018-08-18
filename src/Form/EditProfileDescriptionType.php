<?php

namespace App\Form;

use App\Form\Model\EmailModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditProfileDescriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [ 'label' => 'Description' ])
            ->add('save', SubmitType::class, [ 'label' => 'Save', 'attr' => [ 'cancellink' => true, 'close' => true]]);
    }
}
