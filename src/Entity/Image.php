<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ImageRepository")
 * @ORM\Table(name="image")
 */
class Image
{
    public const DEFAULT_NAME = '/media/default_profile.png';
    public const DEFAULT_PROFILE_IMAGE_URL = '/media/default_profile.png';
    public const DEFAULT_TOKEN_IMAGE_URL = '/media/default_token.png';

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $fileName;

    /** @var string */
    private $url;

    public static function defaultImage(string $url): self
    {
        return (new self())->setFileName(self::DEFAULT_NAME)->setUrl($url);
    }

    /**
     * Sets file.
     *
     * @param string $fileName
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @Groups({"API"})
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url ?? '';
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
