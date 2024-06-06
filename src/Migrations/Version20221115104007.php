<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221115104007 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adjust M:M post_users_likes to have primary key(post, user) instead of ID, adjusted FK/IDX names to doctrine auto-generated';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE post_users_likes MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE post_users_likes DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE post_users_likes DROP FOREIGN KEY FK_PS_Posts');
        $this->addSql('ALTER TABLE post_users_likes DROP FOREIGN KEY FK_PS_Users');
        $this->addSql('ALTER TABLE post_users_likes DROP id');
        $this->addSql('ALTER TABLE post_users_likes ADD PRIMARY KEY (post_id, user_id)');
        $this->addSql('CREATE INDEX IDX_42FD601B4B89032C ON post_users_likes (post_id)');
        $this->addSql('CREATE INDEX IDX_42FD601BA76ED395 ON post_users_likes (user_id)');
        $this->addSql('ALTER TABLE post_users_likes ADD CONSTRAINT FK_PS_Posts FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_users_likes ADD CONSTRAINT FK_PS_Users FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE post_users_likes DROP FOREIGN KEY FK_PS_Posts');
        $this->addSql('ALTER TABLE post_users_likes DROP FOREIGN KEY FK_PS_Users');
        $this->addSql('DROP INDEX idx_42fd601b4b89032c ON post_users_likes');
        $this->addSql('DROP INDEX idx_42fd601ba76ed395 ON post_users_likes');
        $this->addSql('ALTER TABLE post_users_likes DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE post_users_likes ADD id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE post_users_likes ADD CONSTRAINT FK_PS_Posts FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_users_likes ADD CONSTRAINT FK_PS_Users FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }
}
