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
