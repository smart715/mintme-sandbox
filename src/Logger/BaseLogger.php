<?php declare(strict_types = 1);

namespace App\Logger;

use Psr\Log\LoggerInterface;

/** @codeCoverageIgnore  */
abstract class BaseLogger implements LoggerInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /** {@inheritDoc} */
    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /** {@inheritDoc} */
    public function emergency($message, array $context = array()): void
    {
        $this->logger->emergency($message, $context);
    }

    /** {@inheritDoc} */
    public function alert($message, array $context = array()): void
    {
        $this->logger->alert($message, $context);
    }

    /** {@inheritDoc} */
    public function critical($message, array $context = array()): void
    {
        $this->logger->critical($message, $context);
    }

    /** {@inheritDoc} */
    public function error($message, array $context = array()): void
    {
        $this->logger->error($message, $context);
    }

    /** {@inheritDoc} */
    public function warning($message, array $context = array()): void
    {
        $this->logger->warning($message, $context);
    }

    /** {@inheritDoc} */
    public function notice($message, array $context = array()): void
    {
        $this->logger->notice($message, $context);
    }

    /** {@inheritDoc} */
    public function debug($message, array $context = array()): void
    {
        $this->logger->debug($message, $context);
    }

    /** {@inheritDoc} */
    public function log($level, $message, array $context = array()): void
    {
        $this->logger->log($level, $message, $context);
    }
}
