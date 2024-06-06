<?php declare(strict_types = 1);

namespace App\Form;

use App\Form\Type\ValidatorCodeType;
use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\PhoneNumberMailCode;
use App\Validator\Constraints\PhoneNumberSmsCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @codeCoverageIgnore */
class PhoneVerificationType extends AbstractType
{
    private const CODE_MIN_LENGTH = 6;
    private const CODE_MAX_LENGTH = 6;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('smsCode', ValidatorCodeType::class, [
            'label' => $this->translator->trans('phone_confirmation.form.verification_code'),
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Length([
                    'min' => self::CODE_MIN_LENGTH,
                    'max' => self::CODE_MAX_LENGTH,
                ]),
                new PhoneNumberSmsCode(),
            ],
            'failedAttempts' => $options['smsFailedAttempts'],
            'limitReached' => $options['smsLimitReached'],
        ])
        ->add('mailCode', ValidatorCodeType::class, [
            'label' => $this->translator->trans('phone_confirmation.form.email_code'),
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Length([
                    'min' => self::CODE_MIN_LENGTH,
                    'max' => self::CODE_MAX_LENGTH,
                ]),
                new PhoneNumberMailCode(),
            ],
            'failedAttempts' => $options['mailFailedAttempts'],
            'limitReached' => $options['mailLimitReached'],
        ])
        ->add('submit', SubmitType::class, [
                'label' => $this->translator->trans('phone_confirmation.form.submit'),
                'attr' => [
                    'class' => 'btn btn-primary',
                    'type' => 'submit',
                ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('smsFailedAttempts');
        $resolver->setRequired('mailFailedAttempts');
        $resolver->setRequired('smsLimitReached');
        $resolver->setRequired('mailLimitReached');
    }
}
