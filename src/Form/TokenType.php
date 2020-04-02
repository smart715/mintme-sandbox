<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Token\Token;
use App\Form\DataTransformer\NameTransformer;
use App\Form\DataTransformer\XSSProtectionTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore  */
class TokenType extends AbstractType
{
    /** @var NameTransformer  */
    private $nameTransformer;

    /** @var XSSProtectionTransformer */
    private $xssProtectionTransformer;

    public function __construct(
        NameTransformer $nameTransformer,
        XSSProtectionTransformer $xssProtectionTransformer
    ) {
        $this->nameTransformer = $nameTransformer;
        $this->xssProtectionTransformer = $xssProtectionTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('websiteUrl')
            ->add('facebookUrl')
            ->add('telegramUrl')
            ->add('discordUrl')
            ->add('youtubeChannelId')
            ->add('description')
        ;

        $builder->get('name')
            ->addModelTransformer($this->nameTransformer);

        $builder->get('description')
            ->addModelTransformer($this->xssProtectionTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Token::class,
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
