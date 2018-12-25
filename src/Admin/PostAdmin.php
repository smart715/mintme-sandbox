<?php

namespace App\Admin;

use App\Entity\News\Post;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\NewsBundle\Admin\PostAdmin as PostAdminBase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PostAdmin extends PostAdminBase
{
    /** @var RouterInterface */
    private $router;

    /** @var EntityManagerInterface */
    private $em;

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

    /** {@inheritdoc} */
    public function initialize()
    {
        parent::initialize();
        $container = $this->getConfigurationPool()->getContainer();
        $this->em = $container->get('doctrine.orm.default_entity_manager');
        $this->router = $container->get('router');
    }


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

    /** {@inheritdoc} */
    public function postPersist($object): void
    {
        parent::postPersist($object);
    }

    /** {@inheritdoc} */
    public function postUpdate($object): void
    {
        parent::postUpdate($object);
    }

    /** {@inheritdoc} */
    public function postRemove($object): void
    {
        parent::postRemove($object);
    }

    /** {@inheritdoc} */
    public function preBatchAction($actionName, ProxyQueryInterface $query, array &$idx, $allElements): void
    {
        if ($allElements || count($idx) === $this->getPostsCount()) {
        } else {
            /* // for logger
            $qb = $this->em->createQueryBuilder();
            $objects = $qb->select('p')
                ->from(Post::class, 'p')
                ->where($qb->expr()->in('p.id', $idx))
                ->getQuery()
                ->getResult();
             foreach ($objects as $object) {}
             */
        }
        parent::preBatchAction($actionName, $query, $idx, $allElements);
    }

    protected function getPostsCount(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('count(p.id) as count')
            ->from(Post::class, 'p')
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function getPostUrl(Post $post): string
    {
        return $this->router->generate('sonata_news_view', ['permalink' => $post->getTitle()], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
