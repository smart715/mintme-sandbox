<?php declare(strict_types = 1);

namespace App\Entity\Api;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Api\ClientRepository")
 * @ORM\Table(name="api__client")
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="clients", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * @var User
     */
    protected $user;

    /**
     * @codeCoverageIgnore
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @codeCoverageIgnore
     * @param $user User
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getClient(): array
    {
        return ['id' => $this->getPublicId()];
    }
}
