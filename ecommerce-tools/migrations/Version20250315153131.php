<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250315153131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subscription_plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, identifier VARCHAR(50) NOT NULL, price DOUBLE PRECISION NOT NULL, credits INT NOT NULL, description LONGTEXT DEFAULT NULL, features JSON NOT NULL, is_active TINYINT(1) NOT NULL, is_featured TINYINT(1) NOT NULL, display_order INT NOT NULL, stripe_product_id VARCHAR(255) DEFAULT NULL, stripe_price_id VARCHAR(255) DEFAULT NULL, discount INT DEFAULT NULL, term VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, feature_descriptions JSON NOT NULL, UNIQUE INDEX UNIQ_EA664B63772E836A (identifier), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, amount DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, description VARCHAR(255) DEFAULT NULL, package_or_plan VARCHAR(255) DEFAULT NULL, credits INT DEFAULT NULL, stripe_payment_id VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_723705D1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395');
        $this->addSql('DROP TABLE subscription_plan');
        $this->addSql('DROP TABLE transaction');
    }
}
