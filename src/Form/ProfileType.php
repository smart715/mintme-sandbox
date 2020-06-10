<?php declare(strict_types = 1);

namespace App\Form;

use App\Entity\Profile;
use App\Form\DataTransformer\NameTransformer;
use App\Form\DataTransformer\XSSProtectionTransformer;
use App\Form\DataTransformer\ZipCodeTransformer;
use App\Form\Type\BbcodeEditorType;
use App\Form\Type\NicknameType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @codeCoverageIgnore  */
class ProfileType extends AbstractType
{
    /** @var NameTransformer  */
    private $nameTransformer;

    /** @var XSSProtectionTransformer */
    private $xssProtectionTransformer;

    /** @var ZipCodeTransformer */
    private $zipCodeTransformer;

    /** @var bool */
    private $showFullDataInProfile;

    public function __construct(
        NameTransformer $nameTransformer,
        XSSProtectionTransformer $xssProtectionTransformer,
        ZipCodeTransformer $zipCodeTransformer,
        bool $showFullDataInProfile
    ) {
        $this->nameTransformer = $nameTransformer;
        $this->xssProtectionTransformer = $xssProtectionTransformer;
        $this->zipCodeTransformer = $zipCodeTransformer;
        $this->showFullDataInProfile = $showFullDataInProfile;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nickname', NicknameType::class)
            ->add('firstName', TextType::class, [
                'label' => 'First name:',
                'required' => false,
                'attr' => [
                    'maxlength' => 30,
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name:',
                'required' => false,
                'attr' => [
                    'maxlength' => 30,
                ],
            ])
            ->add('country', CountryType::class, [
                'label' => 'Country:',
                'required' => false,
                'placeholder' => 'Select the country',
            ])
            ->add('description', BbcodeEditorType::class, [
                'label' => 'Description:',
                'required' => false,
                'attr' => [
                    'maxlength' => 500,
                ],
            ])
            ->add('anonymous', CheckboxType::class, [
                'label' => 'Hide my personal data',
                'required' => false,
                'attr' => [
                  'class' => 'custom-control-input',
                ],
                'label_attr' => ['class' => 'custom-control-label'],
            ]);

        if ($this->showFullDataInProfile) {
            $builder
                ->add('city', TextType::class, [
                    'label' => 'City:',
                    'required' => false,
                    'attr' => [
                        'minlength' => 2,
                        'maxlength' => 30,
                    ],
                ])
                ->add('zipCode', TextType::class, [
                    'label' => 'ZIP code:',
                    'required' => false,
                ]);
        }

        $builder->get('firstName')
            ->addModelTransformer($this->nameTransformer);

        $builder->get('lastName')
            ->addModelTransformer($this->nameTransformer);

        $builder->get('description')
            ->addModelTransformer($this->xssProtectionTransformer);

        if ($this->showFullDataInProfile) {
            $builder->get('city')
                ->addModelTransformer($this->nameTransformer);

            $builder->get('zipCode')
                ->addModelTransformer($this->zipCodeTransformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Profile::class);
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_intention',
        ]);
    }
}
