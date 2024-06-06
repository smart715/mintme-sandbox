<?php declare(strict_types = 1);

namespace App\Entity;

use App\Repository\TranslationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TranslationRepository::class)
 * @codeCoverageIgnore
 */
class Translation
{
    public const PP = 'pp';
    public const TOS = 'tos';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="bigint", nullable=false)
     */
    protected string $position;

    /**
     * @ORM\Column(type="string", name="translation_for", nullable=false)
     */
    protected string $translationFor;

    /**
     * @ORM\Column(type="string", name="key_translation", nullable=false)
     */
    protected string $keyTranslation;

    /**
     * @ORM\Column(type="string", name="key_language", nullable=false)
     */
    protected string $keyLanguage;

    /**
     * @ORM\Column(type="text", nullable=false, options={"default": ""})
     */
    protected string $content = ''; // phpcs:ignore


    public function __construct(
        string $translationFor,
        string $keyLanguage,
        string $keyTranslation,
        string $content,
        string $position
    ) {
        $this->translationFor = $translationFor;
        $this->keyLanguage = $keyLanguage;
        $this->keyTranslation = $keyTranslation;
        $this->content = $content;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTranslationFor(): string
    {
        return $this->translationFor;
    }

    public function getKeyTranslation(): string
    {
        return $this->keyTranslation;
    }

    public function getKeyLanguage(): string
    {
        return $this->keyLanguage;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setTranslationFor(string $translationFor): self
    {
        $this->translationFor = $translationFor;

        return $this;
    }

    public function setKeyTranslation(string $keyTranslation): self
    {
        $this->keyTranslation = $keyTranslation;

        return $this;
    }

    public function setKeyLanguage(string $keyLanguage): self
    {
        $this->keyLanguage = $keyLanguage;

        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

        return $this;
    }
}
