#!/bin/bash

# Run the database migration to add the seoStatus column
echo "Running database migration..."
php bin/console doctrine:migrations:migrate --no-interaction

# Update SEO status for all existing products
echo "Updating SEO status for existing products..."
php bin/console app:update-seo-status

echo "Migration and update complete!" 