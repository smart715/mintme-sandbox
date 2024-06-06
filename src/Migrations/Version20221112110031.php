<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221112110031 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create comment_tip table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE comment_tip (
                id INT AUTO_INCREMENT NOT NULL,
                comment_id INT NOT NULL,
                token_id INT NOT NULL,
                user_id INT NOT NULL,
                comment_author_id INT NOT NULL,
                amount VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_A711F87CF8697D13 (comment_id),
                INDEX IDX_A711F87C41DEE7B9 (token_id),
                INDEX IDX_A711F87CA76ED395 (user_id),
                INDEX IDX_A711F87C1F0B124D (comment_author_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment_tip ADD CONSTRAINT FK_A711F87CF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE comment_tip ADD CONSTRAINT FK_A711F87C41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id)');
        $this->addSql('ALTER TABLE comment_tip ADD CONSTRAINT FK_A711F87CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment_tip ADD CONSTRAINT FK_A711F87C1F0B124D FOREIGN KEY (comment_author_id) REFERENCES user (id)');
}

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE comment_tip');
    }
}
