<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211108134852 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'table to handle token initial orders from user';
    }
    
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    
        $this->addSql('CREATE TABLE token_init_orders (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, order_id INT NOT NULL, INDEX IDX_FAAB128EA76ED395 (user_id), UNIQUE INDEX token_init_orders_index (user_id, order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE token_init_orders ADD CONSTRAINT FK_FAAB128EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
    
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE token_init_orders DROP FOREIGN KEY FK_FAAB128EA76ED395');
        $this->addSql('DROP TABLE token_init_orders');
    }
}
