@echo off
echo Running database migration...
php bin/console doctrine:migrations:migrate --no-interaction

echo.
echo Updating SEO status for existing products...
php bin/console app:update-seo-status

echo.
echo Migration and update complete!
pause 