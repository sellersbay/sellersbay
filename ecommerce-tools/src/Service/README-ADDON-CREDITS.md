# Add-On Credits System

This document describes the add-on credits system for the ecommerce-tools application. This system allows users to purchase add-on credits that don't expire when their subscription ends.

## Overview

In the previous implementation, all credits were stored directly in the `User` entity with no differentiation between subscription credits and add-on credits. This meant all credits would effectively expire when a subscription ended.

With this new implementation:
- Subscription credits are still stored directly in the `User` entity
- Add-on credits are stored in a separate `AddonCredit` entity with a relationship to the `User`
- Each add-on credit purchase is tracked individually with its own remaining balance
- Transactions record both types of credit operations separately

## Entities

### AddonCredit

This entity represents a single purchase of add-on credits. It tracks:
- The user who owns the credits
- The total amount of credits purchased
- The remaining amount of credits
- The package name the credits were purchased from
- The purchase price
- Whether the credits are still active
- When the credits were last used

### User

The User entity has been updated with:
- A one-to-many relationship to `AddonCredit` entities
- Methods to get and manage add-on credits separately from subscription credits
- A method to get total credits (subscription + add-on)
- A method to use credits intelligently (using add-on credits first, oldest first)

### Transaction

The Transaction entity has been enhanced with:
- New transaction types for add-on credit purchases and usage
- Factory methods to create these new transaction types

## Service

The `AddonCreditService` provides a clean API for working with add-on credits:

```php
// Add add-on credits from a package purchase
$addonCreditService->addCreditsFromPackage($user, $package, $purchasePrice, $stripePaymentId);

// Add add-on credits from an admin adjustment
$addonCreditService->addCreditsFromAdjustment($user, $amount, $notes);

// Use add-on credits
$addonCreditService->useAddonCredits($user, $amount, $description);

// Get total add-on credits for a user
$totalAddonCredits = $addonCreditService->getTotalAddonCredits($user);

// Get all active add-on credit entries for a user
$activeAddons = $addonCreditService->getActiveAddonCredits($user);

// Get all add-on credit transactions for a user
$transactions = $addonCreditService->getAddonCreditTransactions($user);
```

## Credit Usage Logic

The system handles credit usage as follows:

1. When `User::useCredits()` is called, it only uses credits from the subscription balance
2. When `User::useAllCredits()` is called:
   - It first checks if enough credits are available (subscription + add-on)
   - It then uses add-on credits first, starting with the oldest
   - If add-on credits are exhausted, it uses subscription credits

## Examples

### Purchasing Add-On Credits

```php
public function purchaseAddonCredits(User $user, PackageAddOn $package, float $amount, ?string $stripePaymentId = null)
{
    // Create add-on credit record and transaction
    $addonCredit = $this->addonCreditService->addCreditsFromPackage(
        $user,
        $package,
        $amount,
        $stripePaymentId
    );
    
    // Return the add-on credit record
    return $addonCredit;
}
```

### Using Credits

```php
public function generateAIContent(User $user, Product $product, array $options)
{
    $requiredCredits = 2; // Example cost
    
    // Use add-on credits first, then subscription credits
    if (!$user->useAllCredits($requiredCredits)) {
        throw new InsufficientCreditsException('Not enough credits to generate AI content.');
    }
    
    // Generate AI content...
    
    // Record the transaction
    $this->transactionRepository->save(
        Transaction::createCreditUsage($user, $requiredCredits, 'Generated AI content for product ' . $product->getId()),
        true
    );
    
    return $generatedContent;
}
```

## Database Changes

A new `addon_credit` table has been created with the following structure:
- `id` - Primary key
- `user_id` - Foreign key to the user table
- `amount` - Total amount of credits purchased
- `remaining_amount` - Remaining amount of credits
- `package_name` - Name of the package the credits were purchased from
- `transaction_id` - Related transaction ID if applicable
- `purchase_price` - Price paid for the credits
- `is_active` - Whether the credits are still active
- `created_at` - When the credits were purchased
- `updated_at` - When the record was last updated
- `last_used_at` - When the credits were last used
- `notes` - Any additional notes

## Migration

A migration file has been created at `migrations/Version20250323200000.php` to add the necessary database table. Run this migration to update your database schema:

```bash
php bin/console doctrine:migrations:migrate
```

## Implementation Notes

1. This implementation ensures add-on credits don't expire with subscriptions
2. Credits are used in a logical order (oldest add-on credits first, then subscription credits)
3. Each add-on credit purchase is tracked separately for detailed reporting
4. The system maintains backward compatibility with existing code that uses `User::useCredits()`