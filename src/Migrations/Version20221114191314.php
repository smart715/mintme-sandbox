<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221114191314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'top_holders table from TopHolder entity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE top_holders
            (
                id       INT AUTO_INCREMENT NOT NULL,
                token_id INT                NOT NULL,
                user_id  INT                NOT NULL,
                amount   VARCHAR(255)       NOT NULL,
                rank     INT                NOT NULL,
                INDEX IDX_4A519AF341DEE7B9 (token_id),
                INDEX IDX_4A519AF3A76ED395 (user_id),
                UNIQUE INDEX token_user_index (token_id, user_id),
                PRIMARY KEY (id)
            )
                DEFAULT CHARACTER SET utf8mb4
                COLLATE `utf8mb4_unicode_ci`
                ENGINE = InnoDB
        ');
        $this->addSql('ALTER TABLE top_holders ADD CONSTRAINT FK_4A519AF341DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE top_holders ADD CONSTRAINT FK_4A519AF3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE top_holders');
    }
}
