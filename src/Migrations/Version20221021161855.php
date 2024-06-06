<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221021161855 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'modify validation_code to to polymophic relation and add google_auth_entry_id';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE validation_code DROP FOREIGN KEY FK_52SFW547AS57H85B');
        $this->addSql('ALTER TABLE validation_code ADD google_auth_entry_id INT DEFAULT NULL, ADD discr VARCHAR(255) NOT NULL, CHANGE phone_number_id phone_number_id INT NOT NULL, CHANGE code code VARCHAR(6) DEFAULT NULL, CHANGE attempts_date attempts_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE send_date send_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE validation_code ADD CONSTRAINT FK_DCC410DF39DFD528 FOREIGN KEY (phone_number_id) REFERENCES phone_number (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE validation_code ADD CONSTRAINT FK_DCC410DFA4B8D486 FOREIGN KEY (google_auth_entry_id) REFERENCES google_authenticator_entry (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DCC410DFA4B8D486 ON validation_code (google_auth_entry_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE validation_code DROP FOREIGN KEY FK_DCC410DF39DFD528');
        $this->addSql('ALTER TABLE validation_code DROP FOREIGN KEY FK_DCC410DFA4B8D486');
        $this->addSql('DROP INDEX IDX_DCC410DFA4B8D486 ON validation_code');
        $this->addSql('ALTER TABLE validation_code DROP google_auth_entry_id, DROP discr, CHANGE phone_number_id phone_number_id INT DEFAULT NULL, CHANGE code code VARCHAR(6) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE send_date send_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE attempts_date attempts_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE validation_code ADD CONSTRAINT FK_52SFW547AS57H85B FOREIGN KEY (phone_number_id) REFERENCES phone_number (id)');
    }
}
