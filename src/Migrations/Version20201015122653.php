<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201015122653 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql(
            'CREATE TABLE 
                    scheduled_notifications (
                        id INT AUTO_INCREMENT NOT NULL,                         
                        type VARCHAR(255) NOT NULL,
                        user_id INT NOT NULL,
                        time_interval VARCHAR(255) NOT NULL,
                        date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                        date_to_be_send DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',                         
                        PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP TABLE scheduled_notifications');
    }
}
