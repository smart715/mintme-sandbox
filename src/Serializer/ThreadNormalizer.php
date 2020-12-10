<?php declare(strict_types = 1);

namespace App\Serializer;

use App\Entity\Message\Message;
use App\Entity\Message\MessageMetadata;
use App\Entity\Message\Thread;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ThreadNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $normalizer;

    /** @var mixed|User */
    private $user;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        TokenStorageInterface $tokenStorage
    ) {
        $this->normalizer = $objectNormalizer;

        $token = $tokenStorage->getToken();
        $this->user =  $token
            ? $token->getUser()
            : null;
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

        $thread['hasUnreadMessages'] = $this->hasUnreadMessages($object->getMessages());

        return $thread;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Thread;
    }

    /**
     * @param Message[] $messages
     * @return bool
     */
    private function hasUnreadMessages(array $messages): bool
    {
        if (!$this->user instanceof User) {
            return false;
        }

        if (0 === count($messages)) {
            return false;
        }

        $lastMessage = array_pop($messages);

        if ($this->user->getId() === $lastMessage->getSender()->getId()) {
            return false;
        }

        return !$this->isMessageRead($lastMessage);
    }

    private function isMessageRead(Message $message): bool
    {
        /** @var MessageMetadata $metadata */
        foreach ($message->getMetadata() as $metadata) {
            if ($this->user->getId() === $metadata->getParticipant()->getId()) {
                return $metadata->isRead();
            }
        }

        return false;
    }
}
