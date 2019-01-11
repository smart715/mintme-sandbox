<?php

namespace App\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\NewsBundle\Admin\PostAdmin as PostAdminBase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

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
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        parent::configureDatagridFilters($datagridMapper);
        $datagridMapper->remove('author');
    }
}
