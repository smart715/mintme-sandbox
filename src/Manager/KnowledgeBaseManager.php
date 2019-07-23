<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\KnowledgeBase\KnowledgeBase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class KnowledgeBaseManager implements KnowledgeBaseManagerInterface
{
    /** @var EntityRepository  */
    private $kbRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->kbRepository = $entityManager->getRepository(KnowledgeBase::class);
    }

    public function getAll(): array
    {
        return $this->kbRepository->findAll();
    }

    public function getByUrl(string $shortUrl): ?KnowledgeBase
    {
        return $this->kbRepository->findOneBy([
            'shortUrl' => (string)$shortUrl,
        ]);
    }
}
