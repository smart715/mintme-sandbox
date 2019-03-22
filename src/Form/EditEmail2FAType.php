<?php declare(strict_types = 1);

namespace App\Form;

use App\Form\Model\EmailModel;
use App\Validator\Constraints\TwoFactorAuth;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class EditEmail2FAType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', HiddenType::class, [ 'required' => true ])
            ->add('code', TextType::class, [
                'required' => true,
                'constraints' => [ new NotBlank() ],
            ])
            ->add('Verify Code', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', EmailModel::class);
    }
}
