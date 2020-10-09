<?php declare(strict_types = 1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class SetKBPositionCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:kb:position';

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Set initial correct values for positioning function to KB')
        ;
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->em->getConnection()->beginTransaction();

        try {
            $io->progressStart(3);
            $this->updateTable('knowledge_base');
            $io->progressAdvance();
            $this->updateTable('knowledge_base_category');
            $io->progressAdvance();
            $this->updateTable('knowledge_base_subcategory');
            $io->progressAdvance();
            $this->em->getConnection()->commit();
            $io->progressFinish();
            $io->success('Correct positions are set');
        } catch (Throwable $exception) {
            $this->em->getConnection()->rollBack();
            $io->progressFinish();
            $io->error('An error occurred');

            return 1;
        }

        return 0;
    }

    private function updateTable(string $tableName): bool
    {
        $sql = 'SELECT id FROM '.$tableName;
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute([]);
        $rows =  $stmt->fetchAll();
        $count = count($rows);
        
        while ($count-- > 0) {
            $dql = 'UPDATE '.$tableName.' SET position = ? WHERE id = ?';
            $this->em->getConnection()->executeUpdate($dql, [$count, $rows[$count]['id']]);
        }

        return true;
    }
}
