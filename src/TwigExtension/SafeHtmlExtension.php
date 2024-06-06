<?php declare(strict_types = 1);

namespace App\TwigExtension;

use App\Admin\Traits\CheckContentLinksTrait;
use HTMLPurifier;
use HTMLPurifier_Config;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SafeHtmlExtension extends AbstractExtension
{
    public array $internalLinks;
    
    use CheckContentLinksTrait;

    /** @var HTMLPurifier */
    private $purifier;

    public function __construct(string $cachePath, array $internalLinks)
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);

        $this->purifier = new HTMLPurifier($config);
        $this->internalLinks = $internalLinks;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('safeHtml', [$this, 'doSafeHtml']),
        ];
    }

    public function doSafeHtml(string $value): ?string
    {
        $purifiedValue = $this->purifier->purify($value);

        if ($purifiedValue && preg_match('/<a (.*)>(.*)<\/a>/i', $purifiedValue)) {
            $result = $this->addNoopenerNofollowToLinks($purifiedValue, $this->internalLinks);

            if ($result['contentChanged']) {
                return $result['content'];
            }
        }

        return $purifiedValue;
    }
}
