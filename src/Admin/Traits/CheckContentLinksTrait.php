<?php declare(strict_types = 1);

namespace App\Admin\Traits;

trait CheckContentLinksTrait
{
    /** @var array<string> $domainsToSkip */
    public static $domainsToSkip = ['mintme.com', 'coinimp.com', 'webchain.network', 'cba.pl', 'zzz.com.ua'];

    public function addNoreferrerToLinks(string $content): array
    {
        $contentChanged = false;
        $dom = new \DomDocument();
        $dom->loadHTML($content);

        foreach ($dom->getElementsByTagName('a') as $item) {
            $linkUrl = $item->getAttribute('href');
            $urlComponents = parse_url($linkUrl);

            if (is_array($urlComponents) && isset($urlComponents['host'])
                && in_array($urlComponents['host'], self::$domainsToSkip)
            ) {
                continue;
            }

            $initialLinkText = $dom->saveHTML($item);

            $item->setAttribute('rel', 'noreferrer');
            $item->setAttribute('target', '_blank');

            $changedLinkText = $dom->saveHTML($item);

            $content = str_replace((string)$initialLinkText, (string)$changedLinkText, $content);
            $contentChanged = true;
        }

        return [
            'contentChanged' => $contentChanged,
            'content' => $content,
        ];
    }
}
