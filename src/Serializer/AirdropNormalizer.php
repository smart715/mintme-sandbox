<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropAction;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AirdropNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $normalizer;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        TokenStorageInterface $tokenStorage
    ) {
        $this->normalizer = $objectNormalizer;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     *
     * @param Airdrop $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var array $airdrop */
        $airdrop = $this->normalizer->normalize($object, $format, $context);
        $token = $this->tokenStorage->getToken();
        $user = $token
            ? $token->getUser()
            : null;
        $user = $user instanceof User
            ? $user
            : null;

        $map = array_flip(AirdropAction::TYPE_MAP);

        $airdrop['actions'] = [];
        $airdrop['actionsData'] = [];

        /** @var AirdropAction $action */
        foreach ($object->getActions() as $action) {
            $key = $map[$action->getType()];

            $airdrop['actions'][$key] = [
                'id' => $action->getId(),
                'done' => $user ? $action->getUsers()->contains($user) : false,
            ];

            if (null !== $action->getData()) {
                $airdrop['actionsData'][$key] = $action->getData();
            }
        }

        return $airdrop;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Airdrop;
    }
}
