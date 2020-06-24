<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200623101535 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0F3DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0F3DA5256D ON profile (image_id)');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT FK_5F37A13B3DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F37A13B3DA5256D ON token (image_id)');
        $this->addSql('ALTER TABLE crypto ADD image_path VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('UPDATE crypto SET image_path="/media/default_mintme.png" WHERE symbol="WEB"');
        $this->addSql('UPDATE crypto SET image_path="/media/default_btc.png" WHERE symbol="BTC"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE crypto DROP image_path');
        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY FK_8157AA0F3DA5256D');
        $this->addSql('DROP INDEX UNIQ_8157AA0F3DA5256D ON profile');
        $this->addSql('ALTER TABLE token DROP FOREIGN KEY FK_5F37A13B3DA5256D');
        $this->addSql('DROP INDEX UNIQ_5F37A13B3DA5256D ON token');
    }
}
