<?php declare(strict_types = 1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class MainDocumentsAdmin extends AbstractAdmin
{
    public const PROVIDER_NAME = 'app.media.provider.document';

    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name', null)
            ->add('document', null);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('name', null, ['disabled' => true,])
            ->add(
                'document',
                ModelType::class,
                [],
                ['link_parameters' =>
                    [
                        'provider' => self::PROVIDER_NAME,
                        'filter' => ['providerName' => ['value' => self::PROVIDER_NAME]],
                    ],
                ]
            );
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('id', IntegerType::class)
            ->addIdentifier('name', null)
            ->add('document', null);
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('name', null)
            ->add('document', null);
    }

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection->remove('delete');
        $collection->remove('create');
    }
}
