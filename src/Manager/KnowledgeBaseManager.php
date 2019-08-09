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

    public function getByUrl(string $url): ?KnowledgeBase
    {
        return $this->kbRepository->findOneBy([
            'url' => $url,
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
            $category = $kb->getCategory()->getId() ;
            $subcategory = $kb->getSubcategory()
                ? $kb->getSubcategory()->getId()
                : null;

            if (!array_key_exists($category, $parsedKb)) {
                $parsedKb[$category] = [];
            }

            if (!$subcategory) {
                array_unshift($parsedKb[$category], $kb);
            } elseif (!array_key_exists($subcategory, $parsedKb[$category])) {
                $parsedKb[$category][$subcategory] = [];
                array_push($parsedKb[$category][$subcategory], $kb);
            } else {
                array_push($parsedKb[$category][$subcategory], $kb);
            }
        }

        return $parsedKb;
    }
}
