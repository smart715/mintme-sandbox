<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210217130054 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create table for PhonNumber and add foreign keys and join it to Profile table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE phone_number (id INT AUTO_INCREMENT NOT NULL, profile_id INT DEFAULT NULL, phone_number VARCHAR(35) NOT NULL COMMENT \'(DC2Type:phone_number)\', verification_code VARCHAR(6) DEFAULT NULL, verified TINYINT(1) NOT NULL, daily_attempts INT NOT NULL, weekly_attempts INT NOT NULL, monthly_attempts INT NOT NULL, total_attempts INT NOT NULL, attempts_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', edit_attempts INT NOT NULL, edit_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_6B01BC5B6B01BC5B (phone_number), UNIQUE INDEX UNIQ_6B01BC5BCCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE phone_number ADD CONSTRAINT FK_6B01BC5BCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('ALTER TABLE profile ADD phone_number_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0F39DFD528 FOREIGN KEY (phone_number_id) REFERENCES phone_number (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0F39DFD528 ON profile (phone_number_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY FK_8157AA0F39DFD528');
        $this->addSql('DROP TABLE phone_number');
        $this->addSql('DROP INDEX UNIQ_8157AA0F39DFD528 ON profile');
        $this->addSql('ALTER TABLE profile DROP phone_number_id');
    }
}
