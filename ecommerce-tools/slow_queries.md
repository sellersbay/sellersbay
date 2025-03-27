# Slow Query Analysis for SellersBay

## Identified Potentially Slow Queries

Based on code review, the following queries may be slow or inefficient:

### 1. In `WooCommerceProductRepository`

#### `getProductCountsByCategory()`
```php
$results = $this->createQueryBuilder('p')
    ->select('p.status as category_name, COUNT(p.id) as count')
    ->groupBy('p.status')
    ->getQuery()
    ->getResult();
```
**Issue:** Performs a full table scan with GROUP BY. If the products table is large, this will be inefficient.

#### `getAIProcessedProductsByMonth()`
```php
// This method runs 12 separate queries (one for each month)
// Each query is similar to:
$this->createQueryBuilder('p')
    ->select('COUNT(p.id)')
    ->where('p.createdAt >= :startDate')
    ->andWhere('p.createdAt <= :endDate')
    ->andWhere('p.status = :status')
    ->setParameter('startDate', $startDate)
    ->setParameter('endDate', $endDate)
    ->setParameter('status', 'ai_processed')
    ->getQuery()
    ->getSingleScalarResult();
```
**Issue:** Running multiple separate queries in a loop is inefficient. This could be replaced with a single query.

### 2. In `TransactionRepository`

#### `getRevenueByMonth()`
```php
// Runs 12 separate queries (one for each month)
$this->createQueryBuilder('t')
    ->select('SUM(t.amount)')
    ->where('t.createdAt >= :startDate')
    ->andWhere('t.createdAt <= :endDate')
    ->andWhere('t.type IN (:types)')
    ->setParameter('startDate', $startDate)
    ->setParameter('endDate', $endDate)
    ->setParameter('types', [Transaction::TYPE_CREDIT_PURCHASE, Transaction::TYPE_SUBSCRIPTION_PAYMENT])
    ->getQuery()
    ->getSingleScalarResult();
```
**Issue:** Multiple queries in a loop is inefficient. This could be consolidated into a single query.

#### `getRevenueBreakdown()`
```php
// Runs 3 separate sum queries
$totalRevenue = $this->createQueryBuilder('t')
    ->select('SUM(t.amount)')
    ->where('t.amount > 0')
    ->getQuery()
    ->getSingleScalarResult();

$creditPurchaseRevenue = $this->createQueryBuilder('t')
    ->select('SUM(t.amount)')
    ->where('t.type = :type')
    ->setParameter('type', Transaction::TYPE_CREDIT_PURCHASE)
    ->getQuery()
    ->getSingleScalarResult();

$subscriptionRevenue = $this->createQueryBuilder('t')
    ->select('SUM(t.amount)')
    ->where('t.type = :type')
    ->setParameter('type', Transaction::TYPE_SUBSCRIPTION_PAYMENT)
    ->getQuery()
    ->getSingleScalarResult();
```
**Issue:** Three separate queries could be consolidated into one with conditional sums.

### 3. In `UserRepository`

#### `getUserGrowthByMonth()`
```php
// Runs 24 separate queries (2 for each month)
$totalCount = $this->createQueryBuilder('u')
    ->select('COUNT(u.id)')
    ->where('u.createdAt <= :endDate')
    ->setParameter('endDate', $endDate)
    ->getQuery()
    ->getSingleScalarResult();

$activeCount = $this->createQueryBuilder('u')
    ->select('COUNT(u.id)')
    ->where('u.createdAt <= :endDate')
    ->andWhere('u.isVerified = :verified')
    ->setParameter('endDate', $endDate)
    ->setParameter('verified', true)
    ->getQuery()
    ->getSingleScalarResult();
```
**Issue:** Multiple queries in a loop creates significant database load. This could be consolidated.

## Recommendations for Optimization

### 1. Add Missing Indexes

Add indexes for columns frequently used in WHERE clauses and joins:
- `WooCommerceProduct.status`
- `WooCommerceProduct.createdAt`
- `Transaction.createdAt`
- `Transaction.type`
- `User.createdAt`
- `User.isVerified`

Example SQL:
```sql
ALTER TABLE woo_commerce_product ADD INDEX idx_status (status);
ALTER TABLE woo_commerce_product ADD INDEX idx_created_at (created_at);
ALTER TABLE transaction ADD INDEX idx_created_type (created_at, type);
ALTER TABLE user ADD INDEX idx_created_verified (created_at, is_verified);
```

### 2. Consolidate Multiple Queries into Single Queries

#### For `getAIProcessedProductsByMonth()` and `getRevenueByMonth()`:
```php
// Use a more efficient single query with DATE_FORMAT
$results = $this->createQueryBuilder('p')
    ->select('DATE_FORMAT(p.createdAt, \'%Y-%m\') as month, COUNT(p.id) as count')
    ->where('p.createdAt >= :startDate')
    ->andWhere('p.createdAt <= :endDate')
    ->andWhere('p.status = :status')
    ->setParameter('startDate', $startDate) // Set to 12 months ago
    ->setParameter('endDate', $endDate)     // Set to now
    ->setParameter('status', 'ai_processed')
    ->groupBy('month')
    ->orderBy('month', 'ASC')
    ->getQuery()
    ->getResult();
```

#### For `getRevenueBreakdown()`:
```php
$results = $this->createQueryBuilder('t')
    ->select(
        'SUM(t.amount) as total',
        'SUM(CASE WHEN t.type = :credit_type THEN t.amount ELSE 0 END) as credit_purchase',
        'SUM(CASE WHEN t.type = :subscription_type THEN t.amount ELSE 0 END) as subscription'
    )
    ->where('t.amount > 0')
    ->setParameter('credit_type', Transaction::TYPE_CREDIT_PURCHASE)
    ->setParameter('subscription_type', Transaction::TYPE_SUBSCRIPTION_PAYMENT)
    ->getQuery()
    ->getOneOrNullResult();
```

### 3. Implement Caching for Reporting Queries

Many of these queries are for dashboard reporting and don't need real-time data. Implement caching:

```php
public function getProductCountsByCategory(): array
{
    $cacheKey = 'product_counts_by_category';
    $cache = $this->cachePool;
    
    if ($cache->hasItem($cacheKey)) {
        return $cache->getItem($cacheKey)->get();
    }
    
    $results = $this->createQueryBuilder('p')
        ->select('p.status as category_name, COUNT(p.id) as count')
        ->groupBy('p.status')
        ->getQuery()
        ->getResult();
    
    // Process results as before...
    // ...
    
    // Cache for 1 hour
    $cacheItem = $cache->getItem($cacheKey);
    $cacheItem->set($categories);
    $cacheItem->expiresAfter(3600);
    $cache->save($cacheItem);
    
    return $categories;
}
```

### 4. Optimize Data Loading Patterns

For entities with associations, review fetching strategies:
- Use `->join('p.owner')` instead of relying on lazy loading when you know you'll need the association
- Use DTOs for reporting queries instead of loading full entities

### 5. Implement Query Hints for Performance

For complex queries, use Doctrine query hints:
```php
$query = $this->createQueryBuilder('p')
    // ...query definition
    ->getQuery();

// Add query hints
$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Doctrine\ORM\Query\SqlWalker');

return $query->getResult();
```

### 6. Utilize Symfony Profiler for Ongoing Monitoring

Enable the Symfony Web Profiler in development to monitor query performance:
1. Edit `ecommerce-tools/config/packages/web_profiler.yaml`:
   ```yaml
   when@dev:
       web_profiler:
           toolbar: true  # Change to true
           intercept_redirects: false
   ```

2. Access the profiler at `/_profiler` to analyze database queries

### Next Steps

1. Implement these optimizations incrementally 
2. Use EXPLAIN on each critical query to verify improvements
3. Set up SQL query logging to identify slow queries in production
4. Consider adding database server configuration improvements if needed 