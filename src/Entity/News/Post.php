<?php declare(strict_types = 1);

namespace App\Entity\News;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sonata\NewsBundle\Entity\BasePost;

/**
 * @ORM\Entity(repositoryClass="App\Repository\News\PostRepository")
 * @ORM\Table(name="news__post")
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\XmlRoot(name="_post")
 * @codeCoverageIgnore
 */
class Post extends BasePost
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"sonata_api_read","sonata_api_write","sonata_search"})
     * @Serializer\Since(version="1.0")
     * @Serializer\Type(name="integer")
     * @Serializer\SerializedName("id")
     * @Serializer\XmlAttributeMap
     * @Serializer\Expose
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $esTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $arTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $frTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $plTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ptTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ruTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $uaTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $esAbstract;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $arAbstract;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $frAbstract;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $plAbstract;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ptAbstract;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ruAbstract;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $uaAbstract;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $esContent;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $arContent;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $frContent;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $plContent;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ptContent;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ruContent;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $uaContent;

    public function getId(): int
    {
        return $this->id;
    }

    public function getArTitle(): string
    {
        return $this->arTitle ?? '';
    }

    public function setArTitle(?string $arTitle): self
    {
        $this->arTitle = $arTitle;

        return $this;
    }

    public function getEsTitle(): string
    {
        return $this->esTitle ?? '';
    }

    public function setEsTitle(?string $esTitle): self
    {
        $this->esTitle = $esTitle;

        return $this;
    }

    public function getFrTitle(): string
    {
        return $this->frTitle ?? '';
    }

    public function setFrTitle(?string $frTitle): self
    {
        $this->frTitle = $frTitle;

        return $this;
    }

    public function getPlTitle(): string
    {
        return $this->plTitle ?? '';
    }

    public function setPlTitle(?string $plTitle): self
    {
        $this->plTitle = $plTitle;

        return $this;
    }

    public function getPtTitle(): string
    {
        return $this->ptTitle ?? '';
    }

    public function setPtTitle(?string $ptTitle): self
    {
        $this->ptTitle = $ptTitle;

        return $this;
    }

    public function getRuTitle(): string
    {
        return $this->ruTitle ?? '';
    }

    public function setRuTitle(?string $ruTitle): self
    {
        $this->ruTitle = $ruTitle;

        return $this;
    }

    public function getUaTitle(): string
    {
        return $this->uaTitle ?? '';
    }

    public function setUaTitle(?string $uaTitle): self
    {
        $this->uaTitle = $uaTitle;

        return $this;
    }

    public function getArAbstract(): string
    {
        return $this->arAbstract ?? '';
    }

    public function setArAbstract(?string $arAbstract): self
    {
        $this->arAbstract = $arAbstract;

        return $this;
    }

    public function getEsAbstract(): string
    {
        return $this->esAbstract ?? '';
    }

    public function setEsAbstract(?string $esAbstract): self
    {
        $this->esAbstract = $esAbstract;

        return $this;
    }

    public function getFrAbstract(): string
    {
        return $this->frAbstract ?? '';
    }

    public function setFrAbstract(?string $frAbstract): self
    {
        $this->frAbstract = $frAbstract;

        return $this;
    }

    public function getPlAbstract(): string
    {
        return $this->plAbstract ?? '';
    }

    public function setPlAbstract(?string $plAbstract): self
    {
        $this->plAbstract = $plAbstract;

        return $this;
    }

    public function getPtAbstract(): string
    {
        return $this->ptAbstract ?? '';
    }

    public function setPtAbstract(?string $ptAbstract): self
    {
        $this->ptAbstract = $ptAbstract;

        return $this;
    }

    public function getRuAbstract(): string
    {
        return $this->ruAbstract ?? '';
    }

    public function setRuAbstract(?string $ruAbstract): self
    {
        $this->ruAbstract = $ruAbstract;

        return $this;
    }

    public function getUaAbstract(): string
    {
        return $this->uaAbstract ?? '';
    }

    public function setUaAbstract(?string $uaAbstract): self
    {
        $this->uaAbstract = $uaAbstract;

        return $this;
    }

    public function getArContent(): string
    {
        return $this->arContent ?? '';
    }

    public function setArContent(?string $arContent): self
    {
        $this->arContent = $arContent;

        return $this;
    }

    public function getEsContent(): string
    {
        return $this->esContent ?? '';
    }

    public function setEsContent(?string $esContent): self
    {
        $this->esContent = $esContent;

        return $this;
    }

    public function getFrContent(): string
    {
        return $this->frContent ?? '';
    }

    public function setFrContent(?string $frContent): self
    {
        $this->frContent = $frContent;

        return $this;
    }

    public function getPlContent(): string
    {
        return $this->plContent ?? '';
    }

    public function setPlContent(?string $plContent): self
    {
        $this->plContent = $plContent;

        return $this;
    }

    public function getPtContent(): string
    {
        return $this->ptContent ?? '';
    }

    public function setPtContent(?string $ptContent): self
    {
        $this->ptContent = $ptContent ?? '';

        return $this;
    }

    public function getRuContent(): string
    {
        return $this->ruContent ?? '';
    }

    public function setRuContent(?string $ruContent): self
    {
        $this->ruContent = $ruContent;

        return $this;
    }

    public function getUaContent(): string
    {
        return $this->uaContent ?? '';
    }

    public function setUaContent(?string $uaContent): self
    {
        $this->uaContent = $uaContent;

        return $this;
    }
}
