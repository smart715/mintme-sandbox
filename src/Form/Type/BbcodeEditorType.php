<?php declare(strict_types = 1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BbcodeEditorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextareaType::class;
    }

    public function getName(): string
    {
        return 'bbcodeEditorType';
    }
}
