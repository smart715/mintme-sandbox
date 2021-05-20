<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210517105218 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pending_withdraw ADD fee VARCHAR(255) DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE crypto_id crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pending_token_withdraw ADD fee VARCHAR(255) DEFAULT NULL, CHANGE token_id token_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pending_token_withdraw DROP fee, CHANGE token_id token_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pending_withdraw DROP fee, CHANGE crypto_id crypto_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
    }
}
