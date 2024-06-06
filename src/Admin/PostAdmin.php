<?php declare(strict_types = 1);

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;
use Sonata\NewsBundle\Admin\PostAdmin as PostAdminBase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PostAdmin extends PostAdminBase
{
    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

    /** @var mixed[] overriding $datagridValues */
    protected $datagridValues = [

        // display the first page (default = 1)
        '_page' => 1,

        // show posts list in DESC order (default = 'ASC')
        '_sort_order' => 'DESC',

        // name of the ordered field (default = the model's id field, if any)
        '_sort_by' => 'publicationDateStart',
    ];

    protected function configureFormFields(FormMapper $formMapper): void
    {
        parent::configureFormFields($formMapper);
        /** @var mixed $existingObject */
        $existingObject = $this->getSubject();
        $options = $formMapper->get('content')->get('contentFormatter')->getOptions();
        $options = array_merge($options, [
            'choices' => [
                'markdown' => 'markdown',
                'text' => 'text',
                'rawhtml' => 'rawhtml',
                'richhtml' => 'richhtml',
            ],
            'data' => $existingObject->getContentFormatter(),
        ]);
        $rawContent = $formMapper->get('content')->get('rawContent');
        $formMapper->get('content')
            ->remove('contentFormatter')
            ->remove('rawContent')
            ->add('contentFormatter', ChoiceType::class, $options)
            ->add($rawContent);

        $formMapper
            ->with('spanish', [
                'class' => 'col-md-12',
                'label' => 'Spanish translation',
            ])
            ->add('esTitle', TextType::class, [
                'label' => 'ES Title',
                'required' => false,
            ])
            ->add('esAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'ES Abstract',
            ])
            ->add('esContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'ES Content (rich html)',
            ])
            ->end()
            ->with('arabic', [
                'class' => 'col-md-12',
                'label' => 'Arabic translation',
            ])
            ->add('arTitle', TextType::class, [
                'label' => 'AR Title',
                'required' => false,
            ])
            ->add('arAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'AR Abstract',
            ])
            ->add('arContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'AR Content (rich html)',
            ])
            ->end()
            ->with('french', [
                'class' => 'col-md-12',
                'label' => 'French translation',
            ])
            ->add('frTitle', TextType::class, [
                'label' => 'FR Title',
                'required' => false,
            ])
            ->add('frAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'FR Abstract',
            ])
            ->add('frContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'FR Content (rich html)',
            ])
            ->end()
            ->with('polish', [
                'class' => 'col-md-12',
                'label' => 'Polish translation',
            ])
            ->add('plTitle', TextType::class, [
                'label' => 'PL Title',
                'required' => false,
            ])
            ->add('plAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'PL Abstract',
            ])
            ->add('plContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'PL Content (rich html)',
            ])
            ->end()
            ->with('portugal', [
                'class' => 'col-md-12',
                'label' => 'Portuguese translation',
            ])
            ->add('ptTitle', TextType::class, [
                'label' => 'PT Title',
                'required' => false,
            ])
            ->add('ptAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'PT Abstract',
            ])
            ->add('ptContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'PT Content (rich html)',
            ])
            ->end()
            ->with('russian', [
                'class' => 'col-md-12',
                'label' => 'Russian translation',
            ])
            ->add('ruTitle', TextType::class, [
                'label' => 'RU Title',
                'required' => false,
            ])
            ->add('ruAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'RU Abstract',
            ])
            ->add('ruContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'RU Content (rich html)',
            ])
            ->end()
            ->with('ukrainian', [
                'class' => 'col-md-12',
                'label' => 'Ukrainian translation',
            ])
            ->add('uaTitle', TextType::class, [
                'label' => 'UA Title',
                'required' => false,
            ])
            ->add('uaAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'UA Abstract',
            ])
            ->add('uaContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'UA Content (rich html)',
            ])
            ->end()
            ->with('Deutsch', [
                'class' => 'col-md-12',
                'label' => 'Deutsch translation',
            ])
            ->add('deTitle', TextType::class, [
                'label' => 'DE Title',
                'required' => false,
            ])
            ->add('deAbstract', TextareaType::class, [
                'attr' => ['rows' => 5],
                'required' => false,
                'label' => 'DE Abstract',
            ])
            ->add('deContent', SimpleFormatterType::class, [
                'format' => 'richhtml',
                'ckeditor_context' => 'default',
                'required' => false,
                'attr' => ['rows' => 20],
                'label' => 'DE Content (rich html)',
            ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        parent::configureDatagridFilters($datagridMapper);
        $datagridMapper->remove('author');
    }
}
