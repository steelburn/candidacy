#!/bin/bash

# Script to add shared volume mount to all services in docker-compose.yml

echo "Adding shared volume mount to all services..."

SERVICES=(
    "candidate-service"
    "vacancy-service"
    "matching-service"
    "interview-service"
    "offer-service"
    "onboarding-service"
    "reporting-service"
    "admin-service"
    "notification-service"
    "ai-service"
)

for SERVICE in "${SERVICES[@]}"; do
    echo "Updating $SERVICE..."
    
    # Add shared volume mount if not already present
    if ! grep -q "./shared:/var/www/shared" docker-compose.yml | grep -A 5 "$SERVICE:"; then
        # Find the line with the service's volume mount and add shared volume after it
        sed -i "/- \.\/services\/$SERVICE:\/var\/www\/html/a\      - ./shared:/var/www/shared" docker-compose.yml
        echo "  ✓ Added shared volume to $SERVICE"
    else
        echo "  ✓ $SERVICE already has shared volume"
    fi
    
    # Update composer.json path
    COMPOSER_FILE="services/$SERVICE/composer.json"
    if [ -f "$COMPOSER_FILE" ]; then
        # Update the Shared namespace path to use absolute path
        sed -i 's|"Shared\\\\\\\\": "../../../shared/"|"Shared\\\\\\\\": "/var/www/shared/"|g' "$COMPOSER_FILE"
        echo "  ✓ Updated composer.json for $SERVICE"
    fi
done

echo ""
echo "✓ All services updated!"
echo "Now restarting services to apply changes..."
