<?php declare(strict_types = 1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class KnowledgeBase extends AbstractAdmin
{
    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

    /** @var mixed[] overriding $datagridValues */
    protected $datagridValues = [

        // display the first page (default = 1)
        '_page' => 1,

        // show posts list in DESC order (default = 'ASC')
        '_sort_order' => 'ASC',

        // name of the ordered field (default = the model's id field, if any)
        '_sort_by' => 'id',
    ];

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('title')
            ->add('shortUrl')
            ->add('description');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('title', TextType::class)
            ->add('shortUrl', TextType::class)
            ->add('description', TextareaType::class);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('id', IntegerType::class)
            ->add('title', TextType::class)
            ->add('shortUrl', TextType::class)
            ->add('description', TextareaType::class);
    }
}
