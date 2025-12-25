#!/bin/bash

# Environment Setup Script
# Generates .env file from root .env.example

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║          Candidacy Platform - Environment Setup               ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Base directory
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$BASE_DIR"

# Check if root .env.example exists
if [ ! -f ".env.example" ]; then
    echo -e "${RED}✗ Error: .env.example not found in root directory${NC}"
    exit 1
fi

# Generate root .env file
if [ -f ".env" ]; then
    echo -e "${YELLOW}⚠  .env already exists in root directory${NC}"
    echo -e "   Skipping .env generation..."
    echo ""
else
    if cp ".env.example" ".env"; then
        echo -e "${GREEN}✓ Created root .env file${NC}"
        echo ""
    else
        echo -e "${RED}✗ Failed to create .env file${NC}"
        exit 1
    fi
fi

# Summary
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}📋 Setup Complete!${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}✓ Environment file configured${NC}"
echo ""
echo -e "${YELLOW}⚙️  Configuration System:${NC}"
echo -e "   The Candidacy platform uses a ${CYAN}centralized configuration system${NC}."
echo -e "   Application settings are stored in the ${CYAN}database${NC}, not in .env files."
echo ""
echo -e "${BLUE}📝 What's in .env:${NC}"
echo -e "   • Infrastructure settings (Database, Redis, Service URLs)"
echo -e "   • Environment mode (APP_ENV, APP_DEBUG)"
echo -e "   • Logging configuration"
echo ""
echo -e "${BLUE}📝 What's in Database:${NC}"
echo -e "   • AI provider settings (Ollama URL, models)"
echo -e "   • Document parser configuration (Granite Docling)"
echo -e "   • Feature flags (enable AI, auto-matching)"
echo -e "   • Business logic settings (offer expiry, interview reminders)"
echo -e "   • Storage limits and quotas"
echo ""
echo -e "${YELLOW}🚀 Next Steps:${NC}"
echo ""
echo -e "   1. ${CYAN}Review .env file${NC} and update if needed:"
echo -e "      • Database credentials (default: root/root)"
echo -e "      • Redis configuration (default: redis:6379)"
echo ""
echo -e "   2. ${CYAN}Initialize the platform:${NC}"
echo -e "      ${GREEN}make setup${NC}  # Complete platform setup"
echo ""
echo -e "   3. ${CYAN}Start services:${NC}"
echo -e "      ${GREEN}make up${NC}     # Start all services"
echo ""
echo -e "   4. ${CYAN}Seed configuration:${NC}"
echo -e "      Configuration is automatically seeded during 'make setup'"
echo -e "      Or manually: ${GREEN}make seed-config${NC}"
echo ""
echo -e "${BLUE}📚 Documentation:${NC}"
echo -e "   • Configuration reference: ${CYAN}CONFIGURATION.md${NC}"
echo -e "   • Quick start guide: ${CYAN}QUICKSTART.md${NC}"
echo -e "   • Full documentation: ${CYAN}README.md${NC}"
echo ""
echo -e "${GREEN}Done! Ready to start development.${NC}"
echo ""
