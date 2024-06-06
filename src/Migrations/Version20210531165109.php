<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210531165109 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Reward, Reward Volunteers and Reward Participants tables and relations';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reward_volunteers (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reward_id INT NOT NULL, price VARCHAR(255) NOT NULL, note VARCHAR(255) DEFAULT NULL, INDEX IDX_459E1FD4A76ED395 (user_id), INDEX IDX_459E1FD4E466ACA1 (reward_id), UNIQUE INDEX reward_volunteer_index (user_id, reward_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reward_participants (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reward_id INT NOT NULL, price VARCHAR(255) NOT NULL, note VARCHAR(255) DEFAULT NULL, INDEX IDX_19A15687A76ED395 (user_id), INDEX IDX_19A15687E466ACA1 (reward_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reward (id INT AUTO_INCREMENT NOT NULL, token_id INT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, price VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, quantity INT NOT NULL, frozen_amount VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4ED1725341DEE7B9 (token_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reward_volunteers ADD CONSTRAINT FK_459E1FD4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reward_volunteers ADD CONSTRAINT FK_459E1FD4E466ACA1 FOREIGN KEY (reward_id) REFERENCES reward (id)');
        $this->addSql('ALTER TABLE reward_participants ADD CONSTRAINT FK_19A15687A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reward_participants ADD CONSTRAINT FK_19A15687E466ACA1 FOREIGN KEY (reward_id) REFERENCES reward (id)');
        $this->addSql('ALTER TABLE reward ADD CONSTRAINT FK_4ED1725341DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE reward_volunteers DROP FOREIGN KEY FK_459E1FD4E466ACA1');
        $this->addSql('ALTER TABLE reward_participants DROP FOREIGN KEY FK_19A15687E466ACA1');
        $this->addSql('DROP TABLE reward_volunteers');
        $this->addSql('DROP TABLE reward_participants');
        $this->addSql('DROP TABLE reward');
    }
}
