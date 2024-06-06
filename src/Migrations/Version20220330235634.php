<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220330235634 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'fix missing foreign key constraint on donation';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_Donations_Tokens');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A041DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) ON DELETE CASCADE');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE donation DROP FOREIGN KEY FK_31E581A041DEE7B9');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_Donations_Tokens FOREIGN KEY (token_id) REFERENCES token (id)');
    }
}
