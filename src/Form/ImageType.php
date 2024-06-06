<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Image;
use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image as ImageConstraints;

/** @codeCoverageIgnore */
class ImageType extends AbstractType
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
            ->add('file', FileType::class, [
                'label'       => $this->translator->trans('form.file.label'),
                'mapped'      => false,
                'required'    => true,
                'constraints' => [
                    new ImageConstraints([
                        'maxSize'          => '4M',
                        'minWidth'         => $options['is_cover'] ? '784' : '200',
                        'minHeight'        => $options['is_cover'] ? '100' : '200',
                        'mimeTypes'        => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => $this->translator->trans('form.file.valid_img'),
                    ]),
                ],
            ])
            ->add('type', TextType::class, [
                'required' => true,
                'mapped'   => false,
            ])
            ->add('purpose', TextType::class, [
                'required' => true,
                'mapped'   => false,
            ])
             ->add('token', TextType::class, [
                'required' => false,
                'mapped'   => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Image::class);
        $resolver->setDefault('csrf_protection', false);

        $resolver->setRequired('is_cover');
        $resolver->setAllowedTypes('is_cover', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'image';
    }
}
