<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\DiscordRole;
use App\Form\DataTransformer\MoneyTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore */
class DiscordRoleType extends AbstractType
{
    private MoneyTransformer $moneyTransformer;

    public function __construct(MoneyTransformer $moneyTransformer)
    {
        $this->moneyTransformer = $moneyTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requiredBalance', TextType::class)
        ;

        $builder->get('requiredBalance')
            ->addModelTransformer($this->moneyTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DiscordRole::class,
            'allow_extra_fields' => true,
            'csrf_protection' => false,
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
