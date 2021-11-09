<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201027175444 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_Posts_Tokens');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_Posts_Tokens FOREIGN KEY (token_id) REFERENCES token(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_Comments_Posts');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_Comments_Posts FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_Comments_Users');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_Comments_Users FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE `like` DROP FOREIGN KEY FK_Likes_Comments');
        $this->addSql('ALTER TABLE `like` ADD CONSTRAINT FK_Likes_Comments FOREIGN KEY (comment_id) REFERENCES comment(id) ON DELETE CASCADE');

    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_Posts_Tokens');
        $this->addSql('DROP TABLE post');

        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_Comments_Posts');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_Comments_Users');
        $this->addSql('DROP TABLE comment');

        $this->addSql('ALTER TABLE like DROP FOREIGN KEY FK_Likes_Comments');
        $this->addSql('DROP TABLE like');


    }
}
