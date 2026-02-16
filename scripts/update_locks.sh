#!/bin/bash
set -e

SERVICES=(
  "gateway/api-gateway"
  "services/admin-service"
  "services/ai-service"
  "services/auth-service"
  "services/candidate-service"
  "services/document-parser-service"
  "services/interview-service"
  "services/matching-service"
  "services/notification-service"
  "services/offer-service"
  "services/onboarding-service"
  "services/reporting-service"
  "services/tenant-service"
  "services/vacancy-service"
)

for service in "${SERVICES[@]}"; do
  echo "Updating lock file for $service..."
  if [ -d "$service" ]; then
    cd "$service"
    # Use update --lock to only update lock file hash if possible, or full update if needed
    # But usually full update is safer to ensure resolution works
    # Using --ignore-platform-reqs because host php might lack extensions
    composer update --ignore-platform-reqs --no-scripts
    cd -
  else
    echo "Warning: $service not found"
  fi
done
