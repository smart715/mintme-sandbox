<?php

namespace App\Form;

use App\Entity\Token;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TokenFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Token name',])
            ->add('websiteUrl', UrlType::class, [
                'required' => false,
                'label' => 'Website address',
            ])
            ->add('facebookUrl', UrlType::class, [
                'required' => false,
                'label' => 'Facebook',
            ])
            ->add('youtubeUrl', UrlType::class, [
                'required' => false,
                'label' => 'Youtube',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Token::class,
        ]);
    }
}
