<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210114161827 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `media__media`
        VALUES (NULL,\'aml-policy.pdf\',NULL,0,\'app.media.provider.document\',1,\'aml-policy.pdf\',\'{\"filename\":\"aml-policy.pdf\"}\',NULL,NULL,NULL,\'application/pdf\',79215,NULL,NULL,\'default\',NULL,NULL,NULL,NULL,\'2021-01-14 18:28:35\',\'2021-01-14 18:28:35\');');

        $this->addSql('INSERT INTO `main_documents`(`name`) VALUES (\'AML Policy\');');

        $this->addSql('UPDATE `main_documents` SET `media_id` = (SELECT id FROM `media__media` WHERE `name` = \'aml-policy.pdf\' LIMIT 1) WHERE `name`=\'AML Policy\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM `main_documents` WHERE name = \'AML Policy\'');

        $this->addSql('DELETE FROM `media__media` WHERE `name` = \'aml-policy.pdf\'');
    }
}
