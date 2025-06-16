<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Consolidated migration from all previous migrations
 */
final class Version20250504100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Consolidated migration for user table and refresh tokens';
    }

    public function up(Schema $schema): void
    {
        // Create user table (from Version20250501095444)
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (
                id SERIAL NOT NULL, 
                email VARCHAR(180) NOT NULL, 
                username VARCHAR(180) DEFAULT NULL,
                password VARCHAR(255) DEFAULT NULL, 
                roles JSON NOT NULL, 
                first_name VARCHAR(255) DEFAULT NULL, 
                last_name VARCHAR(255) DEFAULT NULL, 
                phone_number VARCHAR(20) DEFAULT NULL,    
                google_id VARCHAR(255) DEFAULT NULL,
                facebook_id VARCHAR(255) DEFAULT NULL,
                apple_id VARCHAR(255) DEFAULT NULL,
                avatar_url VARCHAR(255) DEFAULT NULL,
                is_social_login BOOLEAN DEFAULT FALSE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email, username)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".updated_at IS '(DC2Type:datetime_immutable)'
        SQL);

        // Create refresh_tokens table (from Version20250502145213)
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (
                id SERIAL NOT NULL, 
                refresh_token VARCHAR(128) NOT NULL, 
                username VARCHAR(255) NOT NULL, 
                valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_9BACE7E1C74F2195 ON refresh_tokens (refresh_token)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order
        $this->addSql(<<<'SQL'
            DROP TABLE refresh_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}
