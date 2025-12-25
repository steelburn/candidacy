#!/bin/bash

# Environment Setup Script
# Generates .env files from .env.example templates for all services

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================${NC}"
echo -e "${BLUE}Environment Setup Script${NC}"
echo -e "${BLUE}==================================${NC}"
echo ""

# Base directory
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$BASE_DIR"

# Counter for tracking
CREATED=0
SKIPPED=0
FAILED=0

# Function to generate .env from .env.example
generate_env() {
    local example_file="$1"
    local env_file="${example_file%.example}"
    local relative_path=$(echo "$example_file" | sed "s|$BASE_DIR/||")
    local service_name=$(dirname "$relative_path")
    
    # Clean up service name for display
    if [ "$service_name" = "." ]; then
        service_name="root"
    fi
    
    echo -e "Processing: ${YELLOW}$service_name${NC}"
    
    if [ -f "$env_file" ]; then
        echo -e "  ${YELLOW}⚠${NC}  .env already exists, skipping..."
        ((SKIPPED++))
    else
        if cp "$example_file" "$env_file"; then
            echo -e "  ${GREEN}✓${NC}  Created .env file"
            ((CREATED++))
        else
            echo -e "  ${RED}✗${NC}  Failed to create .env file"
            ((FAILED++))
        fi
    fi
    echo ""
}

# Find all .env.example files dynamically
echo "Searching for .env.example files..."
echo ""

# Use find to locate all .env.example files
while IFS= read -r example_file; do
    generate_env "$example_file"
done < <(find "$BASE_DIR" -name ".env.example" -type f | sort)

# Summary
echo -e "${BLUE}==================================${NC}"
echo -e "${BLUE}Summary${NC}"
echo -e "${BLUE}==================================${NC}"
echo -e "Created: ${GREEN}$CREATED${NC}"
echo -e "Skipped: ${YELLOW}$SKIPPED${NC}"
echo -e "Failed:  ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -gt 0 ]; then
    echo -e "${RED}Some .env files failed to generate. Please check the errors above.${NC}"
    exit 1
fi

if [ $CREATED -gt 0 ]; then
    echo -e "${GREEN}✓ Environment files generated successfully!${NC}"
    echo ""
    echo -e "${YELLOW}IMPORTANT:${NC} Please review and update the generated .env files with your specific configuration:"
    echo ""
    echo "  ${BLUE}Required Updates:${NC}"
    echo "    • Database credentials (DB_PASSWORD, etc.)"
    echo "    • API keys and secrets"
    echo "    • Service URLs (if different from defaults)"
    echo "    • JWT_SECRET and APP_KEY (use 'make generate-secrets')"
    echo ""
    echo "  ${BLUE}Granite Docling Configuration:${NC}"
    echo "    • OLLAMA_URL (default: http://192.168.88.120:11434)"
    echo "    • GRANITE_DOCLING_MODEL (default: ibm/granite-docling:258m)"
    echo "    • DOCLING_TIMEOUT (default: 60)"
    echo "    • DOCLING_IMAGE_RESOLUTION (default: 150)"
    echo ""
    echo "See services/document-parser-service/DOCLING_CONFIG.md for details."
else
    echo -e "${YELLOW}All .env files already exist. No changes made.${NC}"
    echo ""
    echo -e "To regenerate .env files, delete existing ones and run this script again."
fi

echo ""
echo -e "${GREEN}Done!${NC}"
