<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\ValidationCode\ValidationCodeOwner;
use App\Form\Type\ValidatorCodeType;
use App\Validator\Constraints\TFASmsCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @codeCoverageIgnore */
class TFASmsVerificationType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('smsCode', ValidatorCodeType::class, [
            'label' => $this->translator->trans('2fa.backup_codes.form.verification_code'),
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Length(ValidationCodeOwner::CODE_LENGTH),
                new TFASmsCode(),
            ],
            'failedAttempts' => $options['smsFailedAttempts'],
            'limitReached' => $options['smsLimitReached'],
        ])
        ->add('submit', SubmitType::class, [
                'label' => $this->translator->trans('2fa.backup_codes.form.submit'),
                'attr' => [
                    'class' => 'btn btn-primary',
                    'type' => 'submit',
                ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('smsFailedAttempts');
        $resolver->setRequired('smsLimitReached');
    }
}