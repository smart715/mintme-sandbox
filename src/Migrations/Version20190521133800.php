<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190521133800 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE profile CHANGE user_id user_id INT DEFAULT NULL, CHANGE first_name first_name VARCHAR(255) DEFAULT NULL, CHANGE last_name last_name VARCHAR(255) DEFAULT NULL, CHANGE city city VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE country country VARCHAR(255) DEFAULT NULL, CHANGE name_changed_date name_changed_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE page_url page_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE referencer_id referencer_id INT DEFAULT NULL, CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL, CHANGE password_requested_at password_requested_at DATETIME DEFAULT NULL, CHANGE referral_code referral_code VARCHAR(255) DEFAULT NULL, CHANGE hash hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE token CHANGE profile_id profile_id INT DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE website_url website_url VARCHAR(255) DEFAULT NULL, CHANGE facebook_url facebook_url VARCHAR(255) DEFAULT NULL, CHANGE youtube_channel_id youtube_channel_id VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(10000) DEFAULT NULL, CHANGE website_confirmation_token website_confirmation_token VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE google_authenticator_entry CHANGE user_id user_id INT DEFAULT NULL, CHANGE secret secret VARCHAR(255) DEFAULT NULL, CHANGE backup_codes backup_codes JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE pending_withdraw CHANGE user_id user_id INT DEFAULT NULL, CHANGE crypto_id crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE market_status CHANGE crypto_id crypto_id INT DEFAULT NULL, CHANGE quote_token_id quote_token_id INT DEFAULT NULL, CHANGE quote_crypto_id quote_crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE news__comment CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE news__post CHANGE image_id image_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL, CHANGE collection_id collection_id INT DEFAULT NULL, CHANGE publication_date_start publication_date_start DATETIME DEFAULT NULL, CHANGE comments_close_at comments_close_at DATETIME DEFAULT NULL, CHANGE comments_count comments_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media__media CHANGE provider_metadata provider_metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE width width INT DEFAULT NULL, CHANGE height height INT DEFAULT NULL, CHANGE length length NUMERIC(10, 0) DEFAULT NULL, CHANGE content_type content_type VARCHAR(255) DEFAULT NULL, CHANGE content_size content_size INT DEFAULT NULL, CHANGE copyright copyright VARCHAR(255) DEFAULT NULL, CHANGE author_name author_name VARCHAR(255) DEFAULT NULL, CHANGE context context VARCHAR(64) DEFAULT NULL, CHANGE cdn_is_flushable cdn_is_flushable TINYINT(1) DEFAULT NULL, CHANGE cdn_flush_identifier cdn_flush_identifier VARCHAR(64) DEFAULT NULL, CHANGE cdn_flush_at cdn_flush_at DATETIME DEFAULT NULL, CHANGE cdn_status cdn_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media__gallery_media CHANGE gallery_id gallery_id INT DEFAULT NULL, CHANGE media_id media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lock_in CHANGE token_id token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE classification__category CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE context context INT DEFAULT NULL, CHANGE media_id media_id INT DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE position position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE classification__collection CHANGE context context INT DEFAULT NULL, CHANGE media_id media_id INT DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE classification__tag CHANGE context context INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cron_report CHANGE job_id job_id INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE classification__category CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE context context INT DEFAULT NULL, CHANGE media_id media_id INT DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE position position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE classification__collection CHANGE context context INT DEFAULT NULL, CHANGE media_id media_id INT DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE classification__tag CHANGE context context INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cron_report CHANGE job_id job_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE google_authenticator_entry CHANGE user_id user_id INT DEFAULT NULL, CHANGE secret secret VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE backup_codes backup_codes JSON DEFAULT \'NULL\' COLLATE utf8mb4_bin COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE lock_in CHANGE token_id token_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE market_status CHANGE crypto_id crypto_id INT DEFAULT NULL, CHANGE quote_token_id quote_token_id INT DEFAULT NULL, CHANGE quote_crypto_id quote_crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media__gallery_media CHANGE gallery_id gallery_id INT DEFAULT NULL, CHANGE media_id media_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media__media CHANGE provider_metadata provider_metadata LONGTEXT DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:json)\', CHANGE width width INT DEFAULT NULL, CHANGE height height INT DEFAULT NULL, CHANGE length length NUMERIC(10, 0) DEFAULT \'NULL\', CHANGE content_type content_type VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE content_size content_size INT DEFAULT NULL, CHANGE copyright copyright VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE author_name author_name VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE context context VARCHAR(64) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE cdn_is_flushable cdn_is_flushable TINYINT(1) DEFAULT \'NULL\', CHANGE cdn_flush_identifier cdn_flush_identifier VARCHAR(64) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE cdn_flush_at cdn_flush_at DATETIME DEFAULT \'NULL\', CHANGE cdn_status cdn_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE news__comment CHANGE name name VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE url url VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE email email VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE news__post CHANGE image_id image_id INT DEFAULT NULL, CHANGE author_id author_id INT DEFAULT NULL, CHANGE collection_id collection_id INT DEFAULT NULL, CHANGE publication_date_start publication_date_start DATETIME DEFAULT \'NULL\', CHANGE comments_close_at comments_close_at DATETIME DEFAULT \'NULL\', CHANGE comments_count comments_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pending_withdraw CHANGE crypto_id crypto_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE profile CHANGE user_id user_id INT DEFAULT NULL, CHANGE first_name first_name VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE last_name last_name VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE city city VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE country country VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE description description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE name_changed_date name_changed_date DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE page_url page_url VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE token CHANGE profile_id profile_id INT DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE website_url website_url VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE facebook_url facebook_url VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE youtube_channel_id youtube_channel_id VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE description description LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci, CHANGE website_confirmation_token website_confirmation_token VARCHAR(180) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE user CHANGE referencer_id referencer_id INT DEFAULT NULL, CHANGE salt salt VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE last_login last_login DATETIME DEFAULT \'NULL\', CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE password_requested_at password_requested_at DATETIME DEFAULT \'NULL\', CHANGE hash hash VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE referral_code referral_code VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
