<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to create woo_categories table
 */
final class Version20250320180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates woo_categories table for storing WooCommerce categories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE woo_categories (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            category_id VARCHAR(255) NOT NULL,
            store_url VARCHAR(255) NOT NULL,
            owner_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX idx_owner_store (owner_id, store_url),
            UNIQUE INDEX unique_category_store (category_id, store_url, owner_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE woo_categories');
    }
}