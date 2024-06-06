<?php declare(strict_types = 1);

namespace App\Form;

use App\Form\Type\NicknameType;
use App\Form\Type\RegisterRecaptchaType;
use App\Form\Validator\Constraints\RecaptchaTrue;
use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;

/** @codeCoverageIgnore */
class RegistrationType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->remove('username')
            ->remove('plainPassword')
            ->add('nickname', NicknameType::class)
            ->add('recaptcha', RegisterRecaptchaType::class, [
                'attr' => [
                    'options' => [
                        'theme' => 'white',
                        'size' => 'normal',
                        'type'  => 'image',
                        'injectScript' => false,
                        'defer' => true,
                    ],
                ],
                'mapped' => false,
                'constraints' => [ new RecaptchaTrue() ],
                'label' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $this->translator->trans('form.registration.plain_password'),
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ])
        ;
    }

    public function getParent(): string
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix(): string
    {
        return 'app_user_registration';
    }
}
