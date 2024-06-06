<?php declare(strict_types = 1);

namespace App\Form\Type;

use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\IsNotBlacklisted;
use App\Validator\Constraints\NoBadWords;
use App\Validator\Constraints\UniqueNickname;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/** @codeCoverageIgnore */
class NicknameType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('form.nickname.label'),
            'attr' => [
                'minlength' => 2,
                'maxlength' => 30,
                'pattern' => '[A-Za-z\d]+',
                'autocomplete' => 'off',
            ],
            'constraints' => [
                new IsNotBlacklisted([
                    'type' => 'nickname',
                    'caseSensetive' => false,
                    'message' => $this->translator->trans('form.nickname.constraint'),
                ]),
                new UniqueNickname(),
                new NotBlank(),
                new Length([
                    'min' => 2,
                    'max' => 30,
                ]),
                new Regex([
                   'pattern' => '/^[\p{L}\d]+$/u',
                ]),
                new NoBadWords(),
            ],
        ]);
    }


    public function getParent(): string
    {
        return TextType::class;
    }


    public function getName(): string
    {
        return 'NicknameType';
    }
}
