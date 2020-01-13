<?php

namespace App\Entity\Api;

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
     */
    protected $id;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="clients", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @codeCoverageIgnore
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @codeCoverageIgnore
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getClient()
    {
        return ['id' => $this->getPublicId()];
    }


}
