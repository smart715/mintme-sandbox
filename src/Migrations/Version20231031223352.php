<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231031223352 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create inactive_order table';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE inactive_order (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                base_id INT NOT NULL,
                quote_id INT NOT NULL,
                order_id INT NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_FE438F1A76ED395 (user_id),
                INDEX IDX_FE438F16967DF41 (base_id),
                INDEX IDX_FE438F1DB805178 (quote_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql('ALTER TABLE inactive_order ADD CONSTRAINT FK_FE438F1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE inactive_order ADD CONSTRAINT FK_FE438F16967DF41 FOREIGN KEY (base_id) REFERENCES crypto (id)');
        $this->addSql('ALTER TABLE inactive_order ADD CONSTRAINT FK_FE438F1DB805178 FOREIGN KEY (quote_id) REFERENCES crypto (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE inactive_order');
    }
}
