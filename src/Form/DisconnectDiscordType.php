<?php declare(strict_types = 1);

namespace App\Form;

use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/** @codeCoverageIgnore */
class DisconnectDiscordType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setMethod(Request::METHOD_POST)
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-block',
                ],
                'label' => $this->translator->trans('disconnect'),
            ]);
    }
}
