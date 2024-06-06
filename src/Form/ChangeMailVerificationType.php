<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\User;
use App\Form\Type\ValidatorCodeType;
use App\Validator\Constraints\ChangeMailCurrentCode;
use App\Validator\Constraints\ChangeMailNewCode;
use App\Validator\Constraints\TwoFactorAuth;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @codeCoverageIgnore */
class ChangeMailVerificationType extends AbstractType
{
    private const CODE_MIN_LENGTH = 6;
    private const CODE_MAX_LENGTH = 6;

    private User $user;
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator, TokenStorageInterface $tokenStorage)
    {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $tokenStorage->getToken()->getUser();
        $this->user = $user;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentEmailCode', ValidatorCodeType::class, [
                'label' => $this->translator->trans('phone_confirmation.form.verification_code'),
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => self::CODE_MIN_LENGTH,
                        'max' => self::CODE_MAX_LENGTH,
                    ]),
                    new ChangeMailCurrentCode(),
                ],
                'failedAttempts' => $options['currentMailFailedAttempts'],
                'limitReached' => $options['currentMailLimitReached'],
            ])
            ->add('newEmailCode', ValidatorCodeType::class, [
                'label' => $this->translator->trans('phone_confirmation.form.email_code'),
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => self::CODE_MIN_LENGTH,
                        'max' => self::CODE_MAX_LENGTH,
                    ]),
                    new ChangeMailNewCode(),
                ],
                'failedAttempts' => $options['newMailFailedAttempts'],
                'limitReached' => $options['newMailLimitReached'],
            ])
            ->add('submit', SubmitType::class, [
                    'label' => $this->translator->trans('change_email.form.submit'),
                    'attr' => [
                        'class' => 'btn btn-primary',
                        'type' => 'submit',
                    ],
            ]);

        if ($this->user->isGoogleAuthenticatorEnabled()) {
            $builder->add('tfaCode', TextType::class, [
                'translation_domain' => 'messages',
                'label' => '2fa.code',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => $this->translator->trans('2fa.require')]),
                    new TwoFactorAuth(),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('currentMailFailedAttempts');
        $resolver->setRequired('currentMailLimitReached');
        $resolver->setRequired('newMailFailedAttempts');
        $resolver->setRequired('newMailLimitReached');
    }
}
