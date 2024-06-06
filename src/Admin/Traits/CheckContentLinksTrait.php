<?php declare(strict_types = 1);

namespace App\Admin\Traits;

trait CheckContentLinksTrait
{
    public function addNoopenerNofollowToLinks(string $content, array $internalLinks): array
    {
        $contentChanged = false;
        $dom = new \DomDocument();
        $dom->loadHTML($content);

        foreach ($dom->getElementsByTagName('a') as $item) {
            $initialLinkText = $dom->saveHTML($item);
            
            $link = $item->getAttribute('href');
            
            if (!$this->internalLinkMatch($link, $internalLinks)) {
                $item->setAttribute('rel', 'noopener nofollow');
            }
            
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
    
    private function internalLinkMatch(string $item, array $internalLinks): bool
    {
        foreach ($internalLinks as $link) {
            if (preg_match('{^(http(s)?://(www.)?'.$link.')'.'}i', $item)) {
                return true;
            }
        }

        return false;
    }
}
