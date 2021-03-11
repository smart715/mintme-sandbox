<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210309014210 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add title to all posts';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE post set title = CONCAT('Post ', id), slug = CONCAT('post-', id) WHERE title is null");
    }

    public function down(Schema $schema) : void
    {
        $this->addSql("UPDATE post set title = null, slug = null WHERE title = CONCAT('Post ', id)");
    }
}
