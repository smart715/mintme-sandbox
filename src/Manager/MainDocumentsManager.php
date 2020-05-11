<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\MainDocument;
use App\Entity\Media\Media;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class MainDocumentsManager implements MainDocumentsManagerInterfaces
{
    /** @var EntityRepository */
    private $mainDocsRepo;

    public function __construct(EntityManagerInterface $em)
    {
        /** @var EntityRepository $repository */
        $repository = $em->getRepository(MainDocument::class);

        $this->mainDocsRepo = $repository;
    }

    public function findDocPathByName(string $name): ?string
    {
        /** @var MainDocument $media */
        $media = $this->mainDocsRepo->findOneBy(['name' => $name]);

        /** @var Media $document */
        $document = $media->getDocument();

        return $document->getProviderReference();
    }
}
