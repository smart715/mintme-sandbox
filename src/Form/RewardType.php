<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Rewards\Reward;
use App\Form\DataTransformer\MoneyTransformer;
use App\Validator\Constraints\Between;
use App\Validator\Constraints\NoBadWords;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Url;

/** @codeCoverageIgnore */
class RewardType extends AbstractType
{
    private MoneyTransformer $moneyTransformer;

    public function __construct(MoneyTransformer $moneyTransformer)
    {
        $this->moneyTransformer = $moneyTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Length([
                        'min' => 3,
                        'max' => 100,
                    ]),
                    new NoBadWords(),
                ],
            ])
            ->add('price', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Between([
                        'min' => '0.0001',
                        'max' => '100000',
                    ]),
                ],
            ])
            ->add('description', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                    new NoBadWords(),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 999,
                    ]),
                ],
            ]);

        $builder->get('price')
            ->addModelTransformer($this->moneyTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reward::class,
        ]);
    }
}
