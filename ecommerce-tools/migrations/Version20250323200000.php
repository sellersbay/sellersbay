<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Creates the addon_credit table for non-expiring add-on credits.
 */
final class Version20250323200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the addon_credit table for non-expiring add-on credits';
    }

    public function up(Schema $schema): void
    {
        // Create addon_credit table
        $this->addSql('CREATE TABLE addon_credit (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            amount INT NOT NULL,
            remaining_amount INT NOT NULL,
            package_name VARCHAR(255) DEFAULT NULL,
            transaction_id VARCHAR(255) DEFAULT NULL,
            purchase_price DOUBLE PRECISION NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            notes LONGTEXT DEFAULT NULL,
            INDEX IDX_ADDON_CREDIT_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE addon_credit ADD CONSTRAINT FK_ADDON_CREDIT_USER_FK FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop the addon_credit table and its constraints
        $this->addSql('DROP TABLE addon_credit');
    }
}