<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210708184216 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE token set crypto_id = 1 WHERE crypto_id is NULL and deployed = 1");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("UPDATE token set crypto_id = NULL WHERE crypto_id = 1 and deployed = 1");
    }
}
