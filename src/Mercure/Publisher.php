<?php declare(strict_types = 1);

namespace App\Mercure;

use App\Entity\TradableInterface;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Publisher implements PublisherInterface
{
    private HubInterface $hub;
    private NormalizerInterface $normalizer;
    private LoggerInterface $logger;

    public function __construct(
        HubInterface $hub,
        NormalizerInterface $normalizer,
        LoggerInterface $logger
    ) {
        $this->hub = $hub;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
    }

    /**
     * @param string $topic
     * @param mixed $payload
     */
    public function publish(string $topic, $payload, bool $private = false): void
    {
        try {
            /** @var string $payload */
            $payload = json_encode($this->normalize($payload), JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error(
                "Exception thrown when json encoding payload [topic: {$topic}] Error: {$e->getMessage()}"
            );

            return;
        }

        $update = new Update(
            $topic,
            $payload,
            $private
        );

        try {
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->error(
                "Could not publish to Mercure [topic: {$topic}, payload: {$payload}] Error: {$e->getMessage()}"
            );
        }
    }

    /**
     * @param mixed $object
     * @return array|string|int|float|bool|null
     * @throws ExceptionInterface
     */
    private function normalize($object)
    {
        return $this->normalizer->normalize($object, null, [
            'groups' => ['API'],
        ]);
    }

    public function publishWithdrawEvent(User $user, TradableInterface $tradable): void
    {
        $this->publish('withdraw/'.$user->getId(), ['tradable' => $tradable->getSymbol()], true);
    }
}
