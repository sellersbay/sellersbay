<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add seoStatus column to woo_commerce_product table for SEO optimization tracking
 */
final class Version20250326112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add seoStatus column to woo_commerce_product table for SEO optimization tracking';
    }

    public function up(Schema $schema): void
    {
        // Add seoStatus column to woo_commerce_product table
        $this->addSql('ALTER TABLE woo_commerce_product ADD seo_status VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the column if needed
        $this->addSql('ALTER TABLE woo_commerce_product DROP COLUMN seo_status');
    }
} 