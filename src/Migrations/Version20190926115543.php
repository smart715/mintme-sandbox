<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190926115543 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO `media__media` 
        VALUES (44,\'mintMe Press Kit.pdf\',NULL,0,\'app.media.provider.document\',1,\'mintme-press-kit.pdf\',\'{\"filename\":\"mintMe Press Kit.pdf\"}\',NULL,NULL,NULL,\'application/pdf\',357073,NULL,NULL,\'default\',NULL,NULL,NULL,NULL,\'2019-09-26 14:44:02\',\'2019-09-26 14:44:02\');');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DELETE FROM media__media WHERE name = \'mintMe Press Kit.pdf\'');
    }
}
