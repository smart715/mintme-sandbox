<?php declare(strict_types = 1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneVerificationType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {

        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('verificationCode', NumberType::class, [
            'label' => $this->translator->trans('phone_confirmation.form.verification_code'),
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Length([
                    'min' => 6,
                    'max' => 6,
                ]),
            ],
        ])
        ->add('Verify Code', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                    'type' => 'submit',
                ],
        ]);
    }
}
