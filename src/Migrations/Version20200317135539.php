<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200317135539 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE airdrop_participants (
                                id INT AUTO_INCREMENT NOT NULL,
                                user_id INT NOT NULL,
                                airdrop_id INT NOT NULL,
                                INDEX IDX_BC712FC5A76ED395 (user_id),
                                INDEX IDX_BC712FC513543E34 (airdrop_id),
                                UNIQUE INDEX airdrop_participant_index (user_id, airdrop_id),
                                PRIMARY KEY(id)
                            )
                            DEFAULT CHARACTER SET utf8mb4
                            COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE airdrop_participants ADD CONSTRAINT FK_BC712FC513543E34 FOREIGN KEY (airdrop_id) REFERENCES airdrop (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC513543E34');
        $this->addSql('ALTER TABLE airdrop_participants DROP FOREIGN KEY FK_BC712FC5A76ED395');

        $this->addSql('DROP TABLE airdrop_participants');
    }
}
