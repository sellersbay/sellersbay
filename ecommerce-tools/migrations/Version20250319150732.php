<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250319150732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE package_add_on (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, identifier VARCHAR(50) NOT NULL, price_standard DOUBLE PRECISION NOT NULL, price_premium DOUBLE PRECISION NOT NULL, credits INT NOT NULL, per_credit_price_standard DOUBLE PRECISION DEFAULT NULL, per_credit_price_premium DOUBLE PRECISION DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_featured TINYINT(1) NOT NULL, display_order INT NOT NULL, stripe_product_id VARCHAR(255) DEFAULT NULL, stripe_price_id_standard VARCHAR(255) DEFAULT NULL, stripe_price_id_premium VARCHAR(255) DEFAULT NULL, discount INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_BBE8EDE3772E836A (identifier), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE package_add_on');
    }
}
