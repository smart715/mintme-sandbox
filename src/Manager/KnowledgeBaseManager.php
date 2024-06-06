<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\KnowledgeBase\Category;
use App\Entity\KnowledgeBase\KnowledgeBase;
use App\Repository\KnowledgeBase\KnowledgeBaseRepository;

class KnowledgeBaseManager implements KnowledgeBaseManagerInterface
{
    /** @var KnowledgeBaseRepository  */
    private $kbRepository;

    public function __construct(KnowledgeBaseRepository $repository)
    {
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

    public function getRelated(KnowledgeBase $kb): array
    {
        return $this->kbRepository->findKbRelated($kb);
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
                ? $subcategoryObj->getId() . 'key'
                : null;

            if (!array_key_exists($category, $parsedKb)) {
                $parsedKb[$category] = [];
            }

            if (!$subcategory) {
                array_push($parsedKb[$category], $kb);
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
