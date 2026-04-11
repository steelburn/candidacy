#!/bin/bash
cd /home/steelburn/candidacy/services/auth-service

# Mark migrations as done
docker compose exec -T mysql mysql -u root -proot candidacy_auth -e "
INSERT INTO migrations (migration, batch) VALUES ('2026_03_18_143827_create_roles_table', 1)
ON DUPLICATE KEY UPDATE migration=migration;
"

# Run role_user migration
docker compose exec -T auth-service sh -c "APP_ENV=local php artisan migrate --path=database/migrations/2026_03_18_143903_create_role_user_table.php"