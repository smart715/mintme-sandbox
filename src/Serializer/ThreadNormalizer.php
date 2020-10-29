<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Message\Message;
use App\Entity\Message\Thread;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ThreadNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $normalizer;

    public function __construct(
        ObjectNormalizer $objectNormalizer
    ) {
        $this->normalizer = $objectNormalizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param Thread $object
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /**
         * @var array $thread
         */
        $thread = $this->normalizer->normalize($object, $format, $context);

        /** @var Message|null $lastMessage */
        $lastMessage = array_slice($object->getMessages(), -1)[0] ?? null;

        $thread['lastMessageTimestamp'] = $lastMessage
            ? $lastMessage->getCreatedAt()->getTimestamp()
            : $object->getCreatedAt()->getTimestamp();

        return $thread;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Thread;
    }
}
