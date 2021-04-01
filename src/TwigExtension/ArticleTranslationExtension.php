<?php declare(strict_types = 1);

namespace App\TwigExtension;

use App\Entity\KnowledgeBase\Category;
use App\Entity\KnowledgeBase\KnowledgeBase;
use App\Entity\KnowledgeBase\Subcategory;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ArticleTranslationExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('translateArticle', [$this, 'translate']),
        ];
    }

    /**
     * @param Post|KnowledgeBase|Category|Subcategory $value
     * @param string $property
     * @return string
     */
    public function translate($value, string $property): string
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        $propertyLocale = 'get'.ucfirst($locale).ucfirst($property);

        if ('en' === $locale || !method_exists($value, $propertyLocale) || !$value->{$propertyLocale}()) {
            return $value->{'get'.ucfirst($property)}();
        }

        return $value->{$propertyLocale}();
    }
}
