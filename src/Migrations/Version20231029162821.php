<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231029162821 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added promotion history fields to token_promotion table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            ALTER TABLE token_promotion
            ADD user_id INT NOT NULL,
            ADD amount VARCHAR(255) NOT NULL,
            ADD currency VARCHAR(255) DEFAULT \'WEB\' NOT NULL
        ');
        $this->addSql('
            ALTER TABLE token_promotion
            ADD CONSTRAINT FK_B5D8F096A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        ');
        $this->addSql('CREATE INDEX IDX_B5D8F096A76ED395 ON token_promotion (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token_promotion DROP FOREIGN KEY FK_B5D8F096A76ED395');
        $this->addSql('DROP INDEX IDX_B5D8F096A76ED395 ON token_promotion');
        $this->addSql('ALTER TABLE token_promotion DROP user_id, DROP amount, DROP currency');
    }
}
