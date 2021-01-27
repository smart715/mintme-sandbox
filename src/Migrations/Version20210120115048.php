<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210120115048 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE donation AS d INNER JOIN user AS u ON u.id = d.token_creator_id INNER JOIN profile AS p ON u.id = p.user_id INNER JOIN token AS t ON t.profile_id = p.id SET d.token_id = t.id WHERE d.token_id IS NULL;');
    }

    public function down(Schema $schema) : void
    {

    }
}
