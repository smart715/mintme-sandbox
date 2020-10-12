<?php declare(strict_types = 1);

namespace App\Admin\KnowledgeBase;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class KnowledgeBaseSubcategoryAdmin extends AbstractAdmin
{
    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

    /** @var mixed overriding $datagridValues */
    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'ASC',
        '_sort_by' => 'position',
    ];

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper->add('name');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form->add('name', TextType::class);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('id', IntegerType::class)
            ->add('name', TextType::class)
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

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->add('move', $this->getRouterIdParameter().'/move/{position}');
    }
}
