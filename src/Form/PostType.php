<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Post;
use App\Form\DataTransformer\MoneyTransformer;
use App\Form\DataTransformer\XSSProtectionTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore  */
class PostType extends AbstractType
{
    private XSSProtectionTransformer $xssProtectionTransformer;
    private MoneyTransformer $moneyTransformer;

    public function __construct(
        XSSProtectionTransformer $xssProtectionTransformer,
        MoneyTransformer $moneyTransformer
    ) {
        $this->xssProtectionTransformer = $xssProtectionTransformer;
        $this->moneyTransformer = $moneyTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class)
            ->add('amount', TextType::class)
            ->add('title', TextType::class)
            ->add('shareReward', TextType::class)
        ;

        $builder->get('content')
            ->addModelTransformer($this->xssProtectionTransformer);

        $builder->get('amount')
            ->addModelTransformer($this->moneyTransformer);

        $builder->get('shareReward')
            ->addModelTransformer($this->moneyTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
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
