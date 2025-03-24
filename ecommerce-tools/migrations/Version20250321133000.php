<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Add subscription tracking fields to user table
 */
final class Version20250321133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subscription tracking fields to user table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` ADD stripe_subscription_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD next_billing_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP stripe_subscription_id');
        $this->addSql('ALTER TABLE `user` DROP next_billing_date');
    }
}