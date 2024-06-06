<?php declare(strict_types = 1);

namespace App\Form;

use App\Services\TranslatorService\TranslatorInterface;
use App\Validator\Constraints\TwoFactorAuth;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/** @codeCoverageIgnore */
class TwoFactorType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'translation_domain' => 'messages',
                'label' => '2fa.code',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => $this->translator->trans('2fa.require')]),
                    new TwoFactorAuth(),
                ],
            ])
        ;
    }
}
