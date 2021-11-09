<?php declare(strict_types = 1);

namespace App\Utils\Converter\String;

/***
 * Class BbcodeMetaTagsStringStrategy
 *
 * - tags img and url (only url without a name, [url]link[/url] yes, [url=link]name[/url] no)
 * should be trimmed along with content between tags from meta tag description
 *
 * @package App\Utils\Converter
 */

class BbcodeMetaTagsStringStrategy implements StringConverterInterface
{
    public function convert(?string $description): string
    {
        return (string)preg_replace(
            ['/(\[url\].*?\[\/url\])+/','/(\[img\].*?\[\/img\])+/','/(\[yt\].*?\[\/yt\])+/'],
            [],
            $description
        );
    }
}
