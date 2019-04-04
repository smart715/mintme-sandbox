<?php declare(strict_types = 1);

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

            ->add('firstName', TextType::class, [
                'label' => 'First name:',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                    'pattern' => "[A-Za-zÁ-Źá-ź]+[A-Za-zÁ-Źá-ź\s'‘’`´-]*",
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name:',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 30,
                    'pattern' => "[A-Za-zÁ-Źá-ź]+[A-Za-zÁ-Źá-ź\s'‘’`´-]*",
                ],
            ]);

        $builder->get('firstName')
            ->addModelTransformer($this->nameTransformer);

        $builder->get('lastName')
            ->addModelTransformer($this->nameTransformer);
    }
}
