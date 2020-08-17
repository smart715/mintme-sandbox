<?php declare(strict_types = 1);

namespace App\Admin\Traits;

trait CheckContentLinksTrait
{
    /** @var array<string> $domainsToSkip */
    public static $domainsToSkip = [
        'mintme.com', 'www.mintme.com',
        'coinimp.com', 'www.coinimp.com',
        'cba.pl', 'www.cba.pl',
        'zzz.com.ua', 'www.zzz.com.ua',
        'zz.com.ve', 'www.zz.com.ve',
        'aba.ae', 'www.aba.ae',
        'for.ug', 'www.for.ug',
        'lea.com.mx', 'www.lea.com.mx',
        'hit.ng', 'www.hit.ng',
        'server.trading', 'www.server.trading',
        'webchain.network', 'www.webchain.network',
    ];

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
