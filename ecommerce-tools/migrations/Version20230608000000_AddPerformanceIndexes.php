<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add performance optimization indexes to frequently queried fields
 */
final class Version20230608000000_AddPerformanceIndexes extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds performance indexes to frequently queried fields in the database';
    }

    public function up(Schema $schema): void
    {
        // First check if each index exists before trying to create it
        
        // WooCommerceProduct indexes
        $this->skipIf(
            $this->indexExists('woo_commerce_product', 'idx_woocommerce_product_status'),
            'Index idx_woocommerce_product_status already exists on woo_commerce_product table.'
        );
        $this->addSql('CREATE INDEX idx_woocommerce_product_status ON woo_commerce_product (status)');
        
        $this->skipIf(
            $this->indexExists('woo_commerce_product', 'idx_woocommerce_product_created_at'),
            'Index idx_woocommerce_product_created_at already exists on woo_commerce_product table.'
        );
        $this->addSql('CREATE INDEX idx_woocommerce_product_created_at ON woo_commerce_product (created_at)');
        
        $this->skipIf(
            $this->indexExists('woo_commerce_product', 'idx_woocommerce_product_owner'),
            'Index idx_woocommerce_product_owner already exists on woo_commerce_product table.'
        );
        $this->addSql('CREATE INDEX idx_woocommerce_product_owner ON woo_commerce_product (owner_id)');
        
        // Transaction indexes
        $this->skipIf(
            $this->indexExists('transaction', 'idx_transaction_created_at'),
            'Index idx_transaction_created_at already exists on transaction table.'
        );
        $this->addSql('CREATE INDEX idx_transaction_created_at ON transaction (created_at)');
        
        $this->skipIf(
            $this->indexExists('transaction', 'idx_transaction_type'),
            'Index idx_transaction_type already exists on transaction table.'
        );
        $this->addSql('CREATE INDEX idx_transaction_type ON transaction (type)');
        
        $this->skipIf(
            $this->indexExists('transaction', 'idx_transaction_created_type'),
            'Index idx_transaction_created_type already exists on transaction table.'
        );
        $this->addSql('CREATE INDEX idx_transaction_created_type ON transaction (created_at, type)');
        
        $this->skipIf(
            $this->indexExists('transaction', 'idx_transaction_user'),
            'Index idx_transaction_user already exists on transaction table.'
        );
        $this->addSql('CREATE INDEX idx_transaction_user ON transaction (user_id)');
        
        // User indexes
        $this->skipIf(
            $this->indexExists('user', 'idx_user_created_at'),
            'Index idx_user_created_at already exists on user table.'
        );
        $this->addSql('CREATE INDEX idx_user_created_at ON user (created_at)');
        
        $this->skipIf(
            $this->indexExists('user', 'idx_user_verified'),
            'Index idx_user_verified already exists on user table.'
        );
        $this->addSql('CREATE INDEX idx_user_verified ON user (is_verified)');
        
        $this->skipIf(
            $this->indexExists('user', 'idx_user_subscription'),
            'Index idx_user_subscription already exists on user table.'
        );
        $this->addSql('CREATE INDEX idx_user_subscription ON user (subscription_tier)');
    }

    public function down(Schema $schema): void
    {
        // Only drop indexes if they exist
        if ($this->indexExists('woo_commerce_product', 'idx_woocommerce_product_status')) {
            $this->addSql('DROP INDEX idx_woocommerce_product_status ON woo_commerce_product');
        }
        
        if ($this->indexExists('woo_commerce_product', 'idx_woocommerce_product_created_at')) {
            $this->addSql('DROP INDEX idx_woocommerce_product_created_at ON woo_commerce_product');
        }
        
        if ($this->indexExists('woo_commerce_product', 'idx_woocommerce_product_owner')) {
            $this->addSql('DROP INDEX idx_woocommerce_product_owner ON woo_commerce_product');
        }
        
        if ($this->indexExists('transaction', 'idx_transaction_created_at')) {
            $this->addSql('DROP INDEX idx_transaction_created_at ON transaction');
        }
        
        if ($this->indexExists('transaction', 'idx_transaction_type')) {
            $this->addSql('DROP INDEX idx_transaction_type ON transaction');
        }
        
        if ($this->indexExists('transaction', 'idx_transaction_created_type')) {
            $this->addSql('DROP INDEX idx_transaction_created_type ON transaction');
        }
        
        if ($this->indexExists('transaction', 'idx_transaction_user')) {
            $this->addSql('DROP INDEX idx_transaction_user ON transaction');
        }
        
        if ($this->indexExists('user', 'idx_user_created_at')) {
            $this->addSql('DROP INDEX idx_user_created_at ON user');
        }
        
        if ($this->indexExists('user', 'idx_user_verified')) {
            $this->addSql('DROP INDEX idx_user_verified ON user');
        }
        
        if ($this->indexExists('user', 'idx_user_subscription')) {
            $this->addSql('DROP INDEX idx_user_subscription ON user');
        }
    }
    
    /**
     * Check if an index exists on a table
     *
     * @param string $table Table name
     * @param string $indexName Index name
     * @return bool True if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $conn = $this->connection;
        $indexes = $conn->fetchAllAssociative(
            'SHOW INDEX FROM ' . $table . ' WHERE Key_name = :index_name',
            ['index_name' => $indexName]
        );
        
        return count($indexes) > 0;
    }
} 