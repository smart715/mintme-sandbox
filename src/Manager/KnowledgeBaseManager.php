<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\KnowledgeBase\Category;
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
        return $this->parseKnowledgeBases($this->kbRepository->findAll());
    }

    public function getByUrl(string $shortUrl): ?KnowledgeBase
    {
        return $this->kbRepository->findOneBy([
            'shortUrl' => $shortUrl,
        ]);
    }

    /**
     * @param KnowledgeBase[] $knowledgeBases
     * @return array
     */
    private function parseKnowledgeBases(array $knowledgeBases): array
    {
        $parsedKb = [];

        /** @var KnowledgeBase $kb */
        foreach ($knowledgeBases as $kb) {
            if ($kb instanceof KnowledgeBase) {
                $parsedKb[$kb->getCategory()->getId()] = $parsedKb[$kb->getCategory()->getId()] ?? [];
                array_push($parsedKb[$kb->getCategory()->getId()], $kb);
            }
        }

        return $parsedKb;
    }
}
