<?php

namespace App\Form;

use App\Form\Model\EmailModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [ 'label' => 'Email:', 'required' => true, 'attr' => array('pattern' => '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$') ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', EmailModel::class);
    }
}
