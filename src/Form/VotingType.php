<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Voting\Voting;
use App\Form\DataTransformer\XSSProtectionTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore */
class VotingType extends AbstractType
{
    private XSSProtectionTransformer $xssProtectionTransformer;

    public function __construct(
        XSSProtectionTransformer $xssProtectionTransformer
    ) {
        $this->xssProtectionTransformer = $xssProtectionTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('options', CollectionType::class, [
                'entry_type' => OptionType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
            ])
        ;

        $builder->get('description')
            ->addModelTransformer($this->xssProtectionTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voting::class,
        ]);
    }
}
