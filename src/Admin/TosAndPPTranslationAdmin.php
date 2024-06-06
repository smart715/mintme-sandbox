<?php declare(strict_types = 1);

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TosAndPPTranslationAdmin extends AbstractAdmin
{
    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('position')
            ->add('translationFor')
            ->add('keyTranslation')
            ->add('keyLanguage')
            ->add('content');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('position', TextType::class)
            ->add('translationFor', TextType::class)
            ->add('keyTranslation', TextType::class)
            ->add('keyLanguage', TextType::class)
            ->add('content', TextType::class);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('id', IntegerType::class)
            ->addIdentifier('position')
            ->addIdentifier('translationFor')
            ->add('keyTranslation')
            ->add('keyLanguage')
            ->add('content');
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('position')
            ->add('translationFor')
            ->add('keyTranslation')
            ->add('keyLanguage')
            ->add('content');
    }
}
