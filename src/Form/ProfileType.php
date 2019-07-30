<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Profile;
use App\Form\DataTransformer\NameTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore  */
class ProfileType extends AbstractType
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
            ->add('firstName', TextType::class, [
                'label' => 'First name:',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name:',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'City:',
                'required' => false,
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                ],
            ])
            ->add('country', CountryType::class, [
                'label' => 'Country:',
                'required' => false,
                'placeholder' => 'Select the country',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description:',
                'required' => false,
                'attr' => [
                    'maxlength' => 500,
                ],
            ])
            ->add('anonymous', CheckboxType::class, [
                'label' => 'Trade anonymously',
                'required' => false,
                'attr' => [
                  'class' => 'custom-control-input',
                ],
                'label_attr' => ['class' => 'custom-control-label'],
            ]);

        $builder->get('firstName')
            ->addModelTransformer($this->nameTransformer);

        $builder->get('lastName')
            ->addModelTransformer($this->nameTransformer);

        $builder->get('city')
            ->addModelTransformer($this->nameTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Profile::class);
    }
}
