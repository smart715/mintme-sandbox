<?php

namespace App\Entity;

use App\Validator\Constraints as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenRepository")
 * @UniqueEntity("name")
 * @UniqueEntity("address")
 */
class Token
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Regex("/^[a-zA-Z0-9 ]+$/")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    protected $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url()
     * @var string|null
     */
    protected $websiteUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @AppAssert\IsUrlFromDomain("facebook.com")
     * @var string|null
     */
    protected $facebookUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @AppAssert\IsUrlFromDomain("youtube.com")
     * @var string|null
     */
    protected $youtubeUrl;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string|null
     */
    protected $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(string $facebookUrl): self
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function setYoutubeUrl(string $youtubeUrl): self
    {
        $this->youtubeUrl = $youtubeUrl;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
