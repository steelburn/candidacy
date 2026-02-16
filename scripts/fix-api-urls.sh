#!/bin/bash

# Script to update all API calls in api.js to use gateway

FILE="/home/steelburn/Development/candidacy/frontend/web-app/src/services/api.js"

echo "Updating API calls to use gateway..."

# Replace all direct service URLs with relative paths
sed -i "s|'http://localhost:8081/api/|'/api/|g" "$FILE"
sed -i "s|'http://localhost:8082/api/|'/api/|g" "$FILE"
sed -i "s|'http://localhost:8083/api/|'/api/|g" "$FILE"
sed -i "s|'http://localhost:8084/api/|'/api/|g" "$FILE"
sed -i "s|'http://localhost:8085/api/|'/api/|g" "$FILE"
sed-i "s|'http://localhost:8086/api/|'/api/|g" "$FILE"
sed -i "s|'http://localhost:8087/api/|'/api/|g" "$FILE"
sed -i "s|'http://localhost:8089/api/|'/api/|g" "$FILE"
sed -i "s|'http://localhost:8090/api/|'/api/|g" "$FILE"

# For auth API that uses axios directly, use API_GATEWAY_URL
sed -i "s|axios.post('/api/auth/login'|axios.post(\`\${API_GATEWAY_URL}/api/auth/login\`|g" "$FILE"
sed -i "s|axios.post('/api/auth/register'|axios.post(\`\${API_GATEWAY_URL}/api/auth/register\`|g" "$FILE"
sed -i "s|axios.get('/api/setup/check'|axios.get(\`\${API_GATEWAY_URL}/api/setup/check\`|g" "$FILE"
sed -i "s|axios.post('/api/setup/create-admin'|axios.post(\`\${API_GATEWAY_URL}/api/setup/create-admin\`|g" "$FILE"

echo "✓ All API calls now route through gateway"
