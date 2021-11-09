<?php declare(strict_types = 1);

namespace App\Command;

use OldSound\RabbitMqBundle\Command\ConsumerCommand as BaseConsumerCommand;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerCommand extends BaseConsumerCommand
{
    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            parent::execute($input, $output);
        } catch (AMQPConnectionClosedException $e) {
            $this->reconnectAndConsumeAgain($output);
        }

        return 0;
    }

    protected function reconnectAndConsumeAgain(OutputInterface $output): void
    {
        while (true) {
            try {
                $output->writeln('Connection lost. Reconnecting...');
                $this->consumer->reconnect();
                $this->consumer->consume($this->amount);
            } catch (AMQPConnectionClosedException $e) {
                continue;
            }

            break;
        }
    }
}
