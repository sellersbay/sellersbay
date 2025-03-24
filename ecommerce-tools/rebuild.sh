#!/bin/bash
# Script to rebuild Docker containers and fix environment issues

echo "Stopping all running containers..."
docker-compose down

echo "Rebuilding containers (this may take a few minutes)..."
docker-compose build --no-cache

echo "Starting containers..."
docker-compose up -d

echo "Waiting for containers to be ready..."
sleep 10

echo "Installing composer dependencies..."
docker exec roboseo2-php composer install

echo "Clearing Symfony cache..."
docker exec roboseo2-php php bin/console cache:clear

echo "Running database migrations..."
docker exec roboseo2-php php bin/console doctrine:migrations:migrate --no-interaction

echo "Validating database schema..."
docker exec roboseo2-php php bin/console doctrine:schema:validate

echo "Environment rebuild complete. You should now be able to access the application at http://localhost:8000"