<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add targetKeyphrase and metaTitle columns to product table for SEO optimization
 */
final class Version20250324190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add targetKeyphrase and metaTitle columns to product table for SEO optimization';
    }

    public function up(Schema $schema): void
    {
        // Add targetKeyphrase and metaTitle columns to product table
        $this->addSql('ALTER TABLE product 
            ADD target_keyphrase VARCHAR(255) DEFAULT NULL, 
            ADD meta_title VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the columns if needed
        $this->addSql('ALTER TABLE product 
            DROP COLUMN target_keyphrase, 
            DROP COLUMN meta_title');
    }
}