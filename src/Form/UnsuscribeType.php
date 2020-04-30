<?php declare(strict_types = 1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class UnsuscribeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        		->add('mail', HiddenType::class, [
        		])
            ->add('confirm', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                    'type' => 'submit',
                ],
            ]);
    }
}
