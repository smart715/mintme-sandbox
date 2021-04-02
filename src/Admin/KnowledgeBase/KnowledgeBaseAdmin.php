<?php declare(strict_types = 1);

namespace App\Admin\KnowledgeBase;

use App\Admin\Traits\CheckContentLinksTrait;
use App\Entity\KnowledgeBase\KnowledgeBase;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelListType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class KnowledgeBaseAdmin extends AbstractAdmin
{

    use CheckContentLinksTrait;

    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

    /** @var mixed overriding $datagridValues */
    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'ASC',
        '_sort_by' => 'position',
    ];

    /** {@inheritdoc} */
    public function preValidate($object): void
    {
        if ($object instanceof KnowledgeBase && preg_match('/<a (.*)>(.*)<\/a>/i', $object->getDescription())) {
            $result = $this->addNoopenerToLinks($object->getDescription());

            if ($result['contentChanged']) {
                $object->setDescription($result['content']);
            }
        }

        parent::preValidate($object);
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('category', null)
            ->add('subcategory', null)
            ->add('title')
            ->add('esTitle')
            ->add('arTitle')
            ->add('frTitle')
            ->add('plTitle')
            ->add('ptTitle')
            ->add('ruTitle')
            ->add('uaTitle')
            ->add('url')
            ->add('description')
            ->add('esDescription')
            ->add('arDescription')
            ->add('frDescription')
            ->add('plDescription')
            ->add('ptDescription')
            ->add('ruDescription')
            ->add('uaDescription');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('category', ModelListType::class)
            ->add('subcategory', ModelListType::class, [
                'required' => false,
            ])
            ->add('title', TextType::class)
            ->add('url', TextType::class)
            ->add('description', CKEditorType::class, [
                'label' => 'Description (allow HTML tags)',
            ])
            ->end()
            ->with('spanish', [
                'class' => 'col-md-12',
                'label' => 'Spanish translation',
            ])
            ->add('esTitle', TextType::class, [
                'required' => false,
                'label' => 'ES Title',
            ])
            ->add('esDescription', CKEditorType::class, [
                'label' => 'ES Description (allow HTML tags)',
                'required' => false,
            ])
            ->end()
            ->with('arabic', [
                'class' => 'col-md-12',
                'label' => 'Arabic translation',
            ])
            ->add('arTitle', TextType::class, [
                'required' => false,
                'label' => 'AR Title',
            ])
            ->add('arDescription', CKEditorType::class, [
                'label' => 'AR Description (allow HTML tags)',
                'required' => false,
            ])
            ->end()
            ->with('french', [
                'class' => 'col-md-12',
                'label' => 'French translation',
            ])
            ->add('frTitle', TextType::class, [
                'required' => false,
                'label' => 'FR Title',
            ])
            ->add('frDescription', CKEditorType::class, [
                'label' => 'FR Description (allow HTML tags)',
                'required' => false,
            ])
            ->end()
            ->with('polish', [
                'class' => 'col-md-12',
                'label' => 'Polish translation',
            ])
            ->add('plTitle', TextType::class, [
                'required' => false,
                'label' => 'PL Title',
            ])
            ->add('plDescription', CKEditorType::class, [
                'label' => 'PL Description (allow HTML tags)',
                'required' => false,
            ])
            ->end()
            ->with('portugal', [
                'class' => 'col-md-12',
                'label' => 'Portuguese translation',
            ])
            ->add('ptTitle', TextType::class, [
                'required' => false,
                'label' => 'PT Title',
            ])
            ->add('ptDescription', CKEditorType::class, [
                'label' => 'PT Description (allow HTML tags)',
                'required' => false,
            ])
            ->end()
            ->with('russian', [
                'class' => 'col-md-12',
                'label' => 'Russian translation',
            ])
            ->add('ruTitle', TextType::class, [
                'required' => false,
                'label' => 'RU Title',
            ])
            ->add('ruDescription', CKEditorType::class, [
                'label' => 'RU Description (allow HTML tags)',
                'required' => false,
            ])
            ->end()
            ->with('ukrainian', [
                'class' => 'col-md-12',
                'label' => 'Ukrainian translation',
            ])
            ->add('uaTitle', TextType::class, [
                'required' => false,
                'label' => 'UA Title',
            ])
            ->add('uaDescription', CKEditorType::class, [
                'label' => 'UA Description (allow HTML tags)',
                'required' => false,
            ])
            ->end();
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('id', IntegerType::class)
            ->add('category', null)
            ->add('subcategory', null)
            ->addIdentifier('title', TextType::class)
            ->add('url', TextType::class)
            ->add('description', TextareaType::class)
            ->add('_action', null, [
                'label' => false,
                'actions' => [
                    'move' => [
                        'template' => '@PixSortableBehavior/Default/_sort.html.twig',
                        'enable_top_bottom_buttons' => false,
                    ],
                ],
            ])
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('category')
            ->add('subcategory')
            ->add('title')
            ->add('esTitle')
            ->add('arTitle')
            ->add('frTitle')
            ->add('plTitle')
            ->add('ptTitle')
            ->add('ruTitle')
            ->add('uaTitle')
            ->add('url')
            ->add('description', null, ['safe' => true])
            ->add('esDescription', null, ['safe' => true])
            ->add('arDescription', null, ['safe' => true])
            ->add('frDescription', null, ['safe' => true])
            ->add('plDescription', null, ['safe' => true])
            ->add('ptDescription', null, ['safe' => true])
            ->add('ruDescription', null, ['safe' => true])
            ->add('uaDescription', null, ['safe' => true])
        ;
    }

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->add('move', $this->getRouterIdParameter().'/move/{position}');
    }
}
