<?php

namespace App\Form;

use App\Form\DataTransformer\NameTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AddProfileType extends AbstractType
{
    /** @var NameTransformer  */
    private $nameTransformer;

    public function __construct(NameTransformer $nameTransformer)
    {
        $this->nameTransformer = $nameTransformer;
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
<<<<<<< HEAD
            ->add('firstName', TextType::class, [ 'label' => 'First Name:' ])
            ->add('lastName', TextType::class, [ 'label' => 'Last Name:' ]);
=======
            ->add('firstName', TextType::class, [
                'label' => 'First Name',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                    'pattern' => "[A-Za-zÁ-Źá-ź]+[A-Za-zÁ-Źá-ź\s'‘’`´-]*",
                    'title' => 'not valid name',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last Name',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                    'pattern' => "[A-Za-zÁ-Źá-ź]+[A-Za-zÁ-Źá-ź\s'‘’`´-]*",
                    'title' => 'not valid name',
                ],
            ]);

        $builder->get('firstName')
            ->addModelTransformer($this->nameTransformer);

        $builder->get('lastName')
            ->addModelTransformer($this->nameTransformer);
>>>>>>> f380fe97538dff490ac6a8bff4b2ef374600ca55
    }
}
