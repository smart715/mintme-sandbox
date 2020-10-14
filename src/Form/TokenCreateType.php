<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Token\Token;
use App\Form\DataTransformer\NameTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @codeCoverageIgnore  */
class TokenCreateType extends AbstractType
{
    /** @var NameTransformer  */
    private $nameTransformer;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(NameTransformer $nameTransformer, TranslatorInterface $translator)
    {
        $this->nameTransformer = $nameTransformer;
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('form.token.name'),
                'attr' => [
                    'minlength' => Token::NAME_MIN_LENGTH,
                    'max' => Token::NAME_MAX_LENGTH,
                    'pattern' => "[a-zA-Z0-9\s]*",
                    'title' => $this->translator->trans('form.token.name.invalid'),
                ],
            ]);

        $builder->get('name')
            ->addModelTransformer($this->nameTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Token::class,
        ]);
    }
}
