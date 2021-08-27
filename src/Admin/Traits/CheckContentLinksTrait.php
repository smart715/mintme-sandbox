<?php declare(strict_types = 1);

namespace App\Admin\Traits;

trait CheckContentLinksTrait
{
    public function addNoopenerToLinks(string $content): array
    {
        $contentChanged = false;
        $dom = new \DomDocument();
        $dom->loadHTML($content);

        foreach ($dom->getElementsByTagName('a') as $item) {
            $initialLinkText = $dom->saveHTML($item);
            $item->setAttribute('rel', 'noopener');
            $link = $item->getAttribute('href');
            $devPattern = '/^(http(s)?:\/\/(www.)?\S+.mintme.abchosting.abc)/i';
            $prodPattern = '/^(http(s)?:\/\/(www.)?mintme.com)/i';

            $prodResult = preg_match($prodPattern, $link);
            $devResult = preg_match($devPattern, $link);

            if (0 === $prodResult && 0 === $devResult) {
                $item->setAttribute('target', '_blank');
            }

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
