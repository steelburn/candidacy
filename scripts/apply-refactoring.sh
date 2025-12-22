#!/bin/bash

# Script to apply refactoring to all microservices
# Adds HasJsonFields trait to models and BaseApiController to controllers

SERVICES_DIR="/home/steelburn/Development/candidacy/services"

# Array of services to update (excluding candidate and vacancy which are done)
SERVICES=(
    "matching-service"
    "interview-service"
    "offer-service"
    "onboarding-service"
    "reporting-service"
    "admin-service"
    "notification-service"
    "ai-service"
    "auth-service"
)

echo "Starting refactoring for ${#SERVICES[@]} services..."

for SERVICE in "${SERVICES[@]}"; do
    echo ""
    echo "=== Processing $SERVICE ==="
    
    SERVICE_PATH="$SERVICES_DIR/$SERVICE"
    
    # Find all model files
    MODELS=$(find "$SERVICE_PATH/app/Models" -name "*.php" -type f 2>/dev/null | grep -v "/vendor/")
    
    for MODEL in $MODELS; do
        # Skip if already has the trait
        if grep -q "use.*HasJsonFields" "$MODEL"; then
            echo "  ✓ $(basename $MODEL) already has HasJsonFields trait"
            continue
        fi
        
        # Add trait after other use statements in the class
        if grep -q "use HasFactory" "$MODEL"; then
            sed -i '/use HasFactory/a\    use \\Shared\\Traits\\HasJsonFields;' "$MODEL"
            echo "  ✓ Added HasJsonFields trait to $(basename $MODEL)"
        fi
    done
    
    # Find all API controllers
    CONTROLLERS=$(find "$SERVICE_PATH/app/Http/Controllers/Api" -name "*Controller.php" -type f 2>/dev/null | grep -v "Controller.php$" | grep -v "/vendor/")
    
    for CONTROLLER in $CONTROLLERS; do
        # Skip if already extends BaseApiController
        if grep -q "extends BaseApiController" "$CONTROLLER"; then
            echo "  ✓ $(basename $CONTROLLER) already extends BaseApiController"
            continue
        fi
        
        # Replace Controller import and extends
        if grep -q "use App\\\\Http\\\\Controllers\\\\Controller;" "$CONTROLLER"; then
            sed -i 's|use App\\Http\\Controllers\\Controller;|use Shared\\Http\\Controllers\\BaseApiController;|' "$CONTROLLER"
            sed -i 's|extends Controller|extends BaseApiController|' "$CONTROLLER"
            echo "  ✓ Updated $(basename $CONTROLLER) to extend BaseApiController"
        fi
    done
    
    echo "  ✓ $SERVICE complete"
done

echo ""
echo "=== Refactoring Complete ==="
echo "All services have been updated!"
