<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropReferralCode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class AirdropReferralCodeManager implements AirdropReferralCodeManagerInterface
{
    public EntityManagerInterface $entityManager;
    public ObjectRepository $arcRepository;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->arcRepository = $entityManager->getRepository(AirdropReferralCode::class);
    }

    public function encode(AirdropReferralCode $arc): string
    {
        $id = $arc->getId();

        return $this->encodeHash($id);
    }

    public function encodeHash(int $id): string
    {
        $bin = pack('J', $id);
        $hash = base64_encode($bin);
        $hash = ltrim($hash, 'A');
        $hash = rtrim($hash, '=');
        $hash = strtr($hash, '+/', '-_');

        return $hash;
    }

    public function decodeHash(string $hash): int
    {
        $hash = strtr($hash, '-_', '+/');
        $hash = str_pad($hash, 11, 'A', STR_PAD_LEFT);
        $bin = base64_decode($hash);

        return unpack('J', $bin)[1];
    }

    public function decode(string $hash): ?AirdropReferralCode
    {
        $id = $this->decodeHash($hash);

        return $this->getById($id);
    }

    public function getByAirdropAndUser(Airdrop $airdrop, User $user): ?AirdropReferralCode
    {
        /** @var AirdropReferralCode|null $arc */
        $arc = $this->arcRepository->findOneBy(['airdrop' => $airdrop, 'user' => $user]);

        return $arc;
    }

    public function getById(int $id): ?AirdropReferralCode
    {
        /** @var AirdropReferralCode|null $arc */
        $arc = $this->arcRepository->find($id);

        return $arc;
    }

    public function create(Airdrop $airdrop, User $user): AirdropReferralCode
    {
        $arc = (new AirdropReferralCode())
            ->setAirdrop($airdrop)
            ->setUser($user);

        $this->entityManager->persist($arc);
        $this->entityManager->flush();

        return $arc;
    }
}
