<?php declare(strict_types = 1);

namespace App\Mercure;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\PublisherInterface as BasePublisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Publisher implements PublisherInterface
{
    private BasePublisher $publisher;
    private NormalizerInterface $normalizer;
    private LoggerInterface $logger;

    public function __construct(
        BasePublisher $publisher,
        NormalizerInterface $normalizer,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
    }

    /**
     * @param string $topic
     * @param mixed $payload
     */
    public function publish(string $topic, $payload): void
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
            $payload
        );

        try {
            ($this->publisher)($update);
        } catch (\Throwable $e) {
            $this->logger->error(
                "Could not publish to Mercure [topic: {$topic}, payload: {$payload}] Error: {$e->getMessage()}"
            );
        }
    }

    /**
     * @param mixed $object
     * @return array|string|int|float|bool|\ArrayObject|null
     * @throws ExceptionInterface
     */
    private function normalize($object)
    {
        return $this->normalizer->normalize($object, null, [
            'groups' => ['API'],
        ]);
    }
}
