<?php declare(strict_types = 1);

namespace App\EventListener;

use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;

class DoctrineListener
{
    private array $ignoredKeys;

    private array $ignoredColumns;

    public function __construct(array $ignoredKeys, array $ignoredColumns)
    {
        $this->ignoredKeys = $ignoredKeys;
        $this->ignoredColumns = $ignoredColumns;
    }

    public function onSchemaAlterTable(SchemaAlterTableEventArgs $eventArgs): void
    {
        $eventArgs->getTableDiff()->removedIndexes = array_filter(
            $eventArgs->getTableDiff()->removedIndexes,
            fn($index) => !in_array($index->getName(), $this->ignoredKeys)
        );

        $eventArgs->getTableDiff()->addedIndexes = array_filter(
            $eventArgs->getTableDiff()->addedIndexes,
            fn($index) => !in_array($index->getName(), $this->ignoredKeys)
        );

        $eventArgs->getTableDiff()->removedForeignKeys = array_filter(
            $eventArgs->getTableDiff()->removedForeignKeys,
            fn($index) => !in_array(is_string($index) ? $index: $index->getName(), $this->ignoredKeys)
        );

        $eventArgs->getTableDiff()->addedForeignKeys = array_filter(
            $eventArgs->getTableDiff()->addedForeignKeys,
            fn($index) => !in_array($index->getName(), $this->ignoredKeys)
        );
    }

    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $eventArgs): void
    {
        if (in_array($eventArgs->getColumnDiff()->getOldColumnName()->getName(), $this->ignoredColumns)) {
            $eventArgs->preventDefault();
        }
    }
}
