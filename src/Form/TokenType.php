<?php

namespace App\Form;

use App\Entity\Token\Token;
use App\Form\DataTransformer\NameTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TokenType extends AbstractType
{
    /** @var NameTransformer  */
    private $nameTransformer;

    public function __construct(NameTransformer $nameTransformer)
    {
        $this->nameTransformer = $nameTransformer;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('websiteUrl')
            ->add('facebookUrl')
            ->add('youtubeChannelId')
            ->add('description')
        ;

        $builder->get('name')
            ->addModelTransformer($this->nameTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Token::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
