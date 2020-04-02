<?php declare(strict_types = 1);

namespace App\Form;

use App\Validator\Constraints\TwoFactorAuth;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @codeCoverageIgnore  */
class TwoFactorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'required' => true,
                'constraints' => [ new NotBlank(), new TwoFactorAuth() ],
            ])
        ;
    }
}
