@echo off
echo Updating SEO status for all products...
php bin/console app:update-seo-status

echo.
echo Done! Remember to refresh your browser to see the updated SEO status icons.
pause 