<?php declare(strict_types = 1);

namespace App\Form\Type;

use App\Validator\Constraints\IsNotBlacklisted;
use App\Validator\Constraints\UniqueNickname;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class NicknameType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Nickname (Alias):',
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
                    'message' => 'This value is not allowed',
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
