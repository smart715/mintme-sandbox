<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221117105750 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Fix PostUserShareReward entity';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE post_users_share_reward DROP FOREIGN KEY FK_SR_Users');
        $this->addSql('ALTER TABLE post_users_share_reward ADD CONSTRAINT FK_SR_Users FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE post_users_share_reward DROP FOREIGN KEY FK_SR_Users');
        $this->addSql('ALTER TABLE post_users_share_reward ADD CONSTRAINT FK_SR_Users FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }
}
