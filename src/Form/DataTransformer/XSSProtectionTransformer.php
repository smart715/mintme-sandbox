<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class XSSProtectionTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value): string
    {
        return html_entity_decode($value ?? '');
    }

    /** @inheritdoc */
    public function reverseTransform($value): string
    {
        if (false !== strpos($value ?? '', '[yt]')) {
            $value = $this->youTubeURLHandler($value ?? '');
        }
        
        return htmlentities($value ?? '');
    }
    
    private function youTubeURLHandler(string $value): string
    {
        $indexes = $this->strposR($value, 'yt]');
        $indexes = array_reverse($indexes);
        $urls = [];
        $countOfIndex = count($indexes);
        
        for ($i = 0; $i < $countOfIndex; $i+=2) {
            $url = substr($value, (int)$indexes[$i] + 3, (int)$indexes[$i+1] - 2 - strlen($value)) ;
            $urls[] = $url;
        }
    
        foreach ($urls as $url) {
            $value = str_replace('[yt]'.$url.'[/yt]', '[yt]'.$this->extractIdentifierOfVideo($url).'[/yt]', $value);
        }
        
        return $value;
    }
    
    private function extractIdentifierOfVideo(string $value): string
    {
        preg_match('/(?:\/|%3D|v=|vi=)([0-9A-z-_]{11})(?:[%#?&]|$)/', $value, $val);
        
        foreach ($val as $identifier) {
            if (11 === strlen($identifier)) {
                return $identifier;
            }
        }
        
        return $value;
    }
    
    private function strposR(string $haystack, string $needle): array
    {
        $seeks = [];
        
        while ($seek = strrpos($haystack, $needle)) {
            $seeks[] = $seek;
            $haystack = substr($haystack, 0, $seek);
        }
        
        return $seeks;
    }
}
