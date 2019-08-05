<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BbcodeEditorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
    }


    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextareaType::class;
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bbcodeEditorType';
    }
}
