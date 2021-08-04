<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\String\Slugger\AsciiSlugger;

class SlugConverter implements SlugConverterInterface
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function convert(string $from, EntityRepository $repository, $propertyName = 'slug'): string
    {
        $slug = $baseSlug = $this->slugger->slug($from)->toString();

        $i = 2;

        // checking if slug is already taken
        while ($repository->findOneBy([$propertyName => $slug])) {
            $slug = $baseSlug . '-' . $i;

            $i++;
        }

        return $slug;
    }
}

