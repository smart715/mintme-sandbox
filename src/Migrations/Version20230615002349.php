<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230615002349 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Changes logic of token_blocked_users : renames it, changes token id relation to user id relation.
            Also migrates data from token_id to user_id';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE token_blocked_users ADD blocked_user_id INT DEFAULT NULL');

        // migration token_id to user_id
        $this->addSql('
            UPDATE token_blocked_users as bu
            SET bu.blocked_user_id = (
                SELECT user_id FROM profile WHERE id = (
                    SELECT profile_id from token as t WHERE id = bu.token_id
                )
            )
        ');

        // removing duplicates from table
        $this->addSql('
            DELETE t1 FROM token_blocked_users t1
                INNER JOIN token_blocked_users t2 ON t1.user_id = t2.user_id AND t1.blocked_user_id = t2.blocked_user_id
                WHERE t1.id < t2.id
        ');

        $this->addSql('ALTER TABLE token_blocked_users MODIFY blocked_user_id INT NOT NULL');

        $this->addSql('ALTER TABLE token_blocked_users DROP FOREIGN KEY FK_AD099A6C41DEE7B9');
        $this->addSql('DROP INDEX IDX_AD099A6C41DEE7B9 ON token_blocked_users');

        $this->addSql('DROP INDEX token_blocked_user_index ON token_blocked_users');

        // swap user_id and blocked_user_id
        $this->addSql('
            UPDATE token_blocked_users SET
            user_id = (@temp:=user_id),
            user_id = blocked_user_id,
            blocked_user_id = @temp
        ');
    
        $this->addSql('ALTER TABLE token_blocked_users DROP token_id');

        $this->addSql('CREATE INDEX IDX_A3C2E4151EBCBB63 ON token_blocked_users (blocked_user_id)');
        $this->addSql('
            ALTER TABLE token_blocked_users
            ADD CONSTRAINT FK_A3C2E4151EBCBB63 FOREIGN KEY (blocked_user_id) REFERENCES user (id) ON DELETE CASCADE
        ');

        $this->addSql('CREATE UNIQUE INDEX blocked_user_index ON token_blocked_users (user_id, blocked_user_id)');

        $this->addSql('ALTER TABLE token_blocked_users RENAME TO blocked_users');

        $this->addSql('ALTER TABLE blocked_users DROP FOREIGN KEY FK_AD099A6CA76ED395');
        $this->addSql('DROP INDEX IDX_AD099A6CA76ED395 ON blocked_users');

        $this->addSql('CREATE INDEX IDX_A3C2E415A76ED395 ON blocked_users (user_id)');
        $this->addSql('ALTER TABLE blocked_users ADD CONSTRAINT FK_A3C2E415A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE blocked_users ADD token_id INT DEFAULT NULL');

        $this->addSql('
            UPDATE blocked_users as bu
            SET bu.token_id = (
                SELECT id FROM token WHERE profile_id = (
                    SELECT id FROM profile WHERE user_id = bu.user_id
                ) LIMIT 1
            )
        ');

        $this->addSql('ALTER TABLE blocked_users MODIFY token_id INT NOT NULL');

        $this->addSql('CREATE INDEX IDX_AD099A6C41DEE7B9 ON blocked_users (token_id)');
        $this->addSql('
            ALTER TABLE blocked_users
            ADD CONSTRAINT FK_AD099A6C41DEE7B9 FOREIGN KEY (token_id) REFERENCES token (id) ON DELETE CASCADE
        ');

        $this->addSql('DROP INDEX blocked_user_index ON blocked_users');

        // swap user_id and blocked_user_id
        $this->addSql('UPDATE blocked_users SET user_id = blocked_user_id');

        $this->addSql('ALTER TABLE blocked_users DROP FOREIGN KEY FK_A3C2E4151EBCBB63');
        $this->addSql('DROP INDEX IDX_A3C2E4151EBCBB63 ON blocked_users');

        $this->addSql('ALTER TABLE blocked_users DROP blocked_user_id');

        $this->addSql('CREATE UNIQUE INDEX token_blocked_user_index ON blocked_users (user_id, token_id)');

        $this->addSql('ALTER TABLE blocked_users DROP FOREIGN KEY FK_A3C2E415A76ED395');
        $this->addSql('DROP INDEX IDX_A3C2E415A76ED395 ON blocked_users');

        $this->addSql('CREATE INDEX IDX_AD099A6CA76ED395 ON blocked_users (user_id)');
        $this->addSql('ALTER TABLE blocked_users ADD CONSTRAINT FK_AD099A6CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE blocked_users RENAME TO token_blocked_users');
    }

    // insert all user tokens to restore previous database state
    public function postDown(Schema $schema): void
    {
        $query = "SELECT user_id, token_id, created from token_blocked_users";
        $data = $this->connection->prepare($query);
        $data->execute();

        $count = 0;
        $dataToInsert = [];

        foreach($data as $row) {
            $tokensQuery = "
                SELECT id from token
                WHERE profile_id = (SELECT profile_id from token where id = :tokenId)
                AND id != :tokenId
            ";
            $tokensData = $this->connection->prepare($tokensQuery);
            $tokensData->bindParam("tokenId", $row['token_id']);
            $tokensData->execute();

            foreach($tokensData as $token) {
                $userId = $row['user_id'];
                $tokenId = $token['id'];
                $createdAt = $row['created'];
                $dataToInsert[] = "($userId, $tokenId, '$createdAt')";
            }

            if ($tokensData->rowCount() > 0) {
                $count++;
            }

            if ($count > 20) {
                $count = 0;

                $this->insertBlockedUserRecords($dataToInsert);

                $dataToInsert = [];
            }
        }

        if ($count > 0) {
            $this->insertBlockedUserRecords($dataToInsert);
        }
    }

    private function insertBlockedUserRecords(array $dataToInsert)
    {
        $this->connection->executeQuery("
            INSERT INTO token_blocked_users (user_id, token_id, created)
            VALUES " . implode(', ', $dataToInsert)
        );
    }
}
