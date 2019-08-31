<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190829122559 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_tokens DROP FOREIGN KEY FK_CF080AB341DEE7B9');
        $this->addSql('ALTER TABLE user_tokens ADD CONSTRAINT FK_CF080AB341DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_tokens DROP FOREIGN KEY FK_CF080AB341DEE7B9');
        $this->addSql('ALTER TABLE user_tokens ADD CONSTRAINT FK_CF080AB341DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
    }
}
