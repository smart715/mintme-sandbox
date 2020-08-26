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
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class KnowledgeBaseAdmin extends AbstractAdmin
{

    use CheckContentLinksTrait;

    /** @var bool overriding $supportsPreviewMode */
    public $supportsPreviewMode = true;

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
            ->add('url')
            ->add('description');
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
            ]);
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('id', IntegerType::class)
            ->add('category', null)
            ->add('subcategory', null)
            ->addIdentifier('title', TextType::class)
            ->add('url', TextType::class)
            ->add('description', TextareaType::class);
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('category')
            ->add('subcategory')
            ->add('title')
            ->add('url')
            ->add('description', null, ['safe' => true])
        ;
    }
}
