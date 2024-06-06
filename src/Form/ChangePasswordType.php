<?php declare(strict_types = 1);

namespace App\Form;

use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @codeCoverageIgnore */
class ChangePasswordType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $constraintsOptions = [
            'message' => 'fos_user.current_password.invalid',
        ];

        if (!empty($options['validation_groups'])) {
            $constraintsOptions['groups'] = [reset($options['validation_groups'])];
        }

        $builder
            ->remove('plainPassword')
            ->add('current_password', PasswordType::class, [
                'label' => $this->translator->trans('form.change_password.current_password'),
                'translation_domain' => 'FOSUserBundle',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(),
                    new UserPassword($constraintsOptions),
                ],
                'attr' => [
                    'autocomplete' => 'current-password',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $this->translator->trans('form.change_password.plain_password'),
                'translation_domain' => 'FOSUserBundle',
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ])
        ;
    }

    public function getParent(): string
    {
        return 'FOS\UserBundle\Form\Type\ChangePasswordFormType';
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return 'app_user_change_password';
    }
}
