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
        /** @var EntityRepository $repository */
        $repository = $entityManager->getRepository(KnowledgeBase::class);

        $this->kbRepository = $repository;
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
            /** @var Category $categoryObj */
            $categoryObj = $kb->getCategory();
            $category = $categoryObj->getId();
            $subcategoryObj = $kb->getSubcategory();
            $subcategory = is_object($subcategoryObj)
                ? $subcategoryObj->getId()
                : null;

            if (!array_key_exists($category, $parsedKb)) {
                $parsedKb[$category] = [];
            }

            if (!$subcategory) {
                $parsedKb[$category][] = $kb;
            } else {
                if (!isset($parsedKb[$category][$subcategory])) {
                    $parsedKb[$category][$subcategory] = [];
                }

                if (is_array($parsedKb[$category][$subcategory])) {
                    $parsedKb[$category][$subcategory][] = $kb;
                }
            }
        }

        return $parsedKb;
    }
}
