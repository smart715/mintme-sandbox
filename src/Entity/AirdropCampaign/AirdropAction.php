<?php declare(strict_types = 1);

namespace App\Entity\AirdropCampaign;

use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class AirdropAction
{
    public const TYPE_MAP = [
        'twitterMessage' => 0,
        'twitterRetweet' => 1,
        'facebookMessage' => 2,
        'facebookPage' => 3,
        'facebookPost' => 4,
        'linkedinMessage' => 5,
        'youtubeSubscribe' => 6,
        'postLink' => 7,
    ];

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AirdropCampaign\Airdrop", inversedBy="actions")
     */
    protected Airdrop $airdrop;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User")
     * @ORM\JoinTable(name="airdrop_action_user",
     *      joinColumns={@ORM\JoinColumn(name="airdrop_action_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     * @var ArrayCollection
     */
    protected $users;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $data;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function addUser(User $user): self
    {
        $this->users->add($user);

        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function setAirdrop(Airdrop $airdrop): self
    {
        $this->airdrop = $airdrop;

        return $this;
    }

    public function getAirdrop(): Airdrop
    {
        return $this->airdrop;
    }

    public function setData(?string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }
}
