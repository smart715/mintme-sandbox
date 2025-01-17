<?php declare(strict_types = 1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore */
class ValidatorCodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('failedAttempts');
        $resolver->setRequired('limitReached');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['failedAttempts'] = $options['failedAttempts'];
        $view->vars['limitReached'] = $options['limitReached'];
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
