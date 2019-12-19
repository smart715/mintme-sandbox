<?php

namespace App\Entity\Api;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ApiClientRepository")
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;


    public function __construct()
    {
        parent::__construct();
        // your own logic
    }
}
