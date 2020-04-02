<?php declare(strict_types = 1);

namespace App\Admin\KnowledgeBase;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class KnowledgeBaseCategoryAdmin extends AbstractAdmin
{
    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

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
            ->add('name', TextType::class);
    }
}
