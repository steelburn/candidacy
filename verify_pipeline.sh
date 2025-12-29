#!/bin/bash

BASE_URL="http://localhost:8080"
USERNAME="admin@test.com"
PASSWORD="password"
RESUME_PATH="test_resume.pdf"

echo "🚀 Starting Automated Verification Pipeline..."

# 1. Login
echo -e "\n🔑 Step 1: Authenticating..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$USERNAME\", \"password\": \"$PASSWORD\"}")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.access_token')

if [ "$TOKEN" == "null" ] || [ -z "$TOKEN" ]; then
  echo "❌ Login failed. Response: $LOGIN_RESPONSE"
  exit 1
fi
echo "✅ Login successful."

# 1.5 Create Candidate
echo -e "\n👤 Step 1.5: Creating Test Candidate..."
TIMESTAMP=$(date +%s)
CREATE_RESP=$(curl -s -X POST "$BASE_URL/api/candidates" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"name\": \"Test Candidate\", \"email\": \"test${TIMESTAMP}@candidate.com\", \"status\": \"new\"}")

CANDIDATE_ID=$(echo $CREATE_RESP | jq -r '.id')

if [ "$CANDIDATE_ID" == "null" ] || [ -z "$CANDIDATE_ID" ]; then
    echo "❌ Candidate creation failed. Response: $CREATE_RESP"
    exit 1
fi
echo "✅ Candidate created with ID: $CANDIDATE_ID"

# 2. Upload
echo -e "\nPAGE: 2: Uploading Resume $RESUME_PATH for Candidate ID $CANDIDATE_ID..."
UPLOAD_RESPONSE=$(curl -s -X POST "$BASE_URL/api/candidates/$CANDIDATE_ID/cv" \
  -H "Authorization: Bearer $TOKEN" \
  -F "cv_file=@$RESUME_PATH")

if [[ $UPLOAD_RESPONSE != *"success"* && $UPLOAD_RESPONSE != *"job_id"* ]]; then
     if [[ $UPLOAD_RESPONSE == *"message"* ]]; then
          echo "✅ Upload initiated."
     else
          echo "❌ Upload failed/Unknown response: $UPLOAD_RESPONSE"
          exit 1
     fi
else
     echo "✅ Upload successful."
fi


# 3. Poll
echo -e "\n⏳ Step 3: Polling for Parsing Results..."
MAX_RETRIES=60
PARSING_COMPLETE=false

for ((i=1;i<=MAX_RETRIES;i++)); do
  echo -n "."
  sleep 2
  CANDIDATE_RESP=$(curl -s -X GET "$BASE_URL/api/candidates/$CANDIDATE_ID" -H "Authorization: Bearer $TOKEN")
  
  # Check if parsed_data is not null/empty
  IS_PARSED=$(echo $CANDIDATE_RESP | jq -r '.data.parsed_data != null')
  
  if [ "$IS_PARSED" == "true" ]; then
    PARSING_COMPLETE=true
    PARSED_DATA=$(echo $CANDIDATE_RESP | jq '.data.parsed_data')
    break
  fi
done
echo ""

if [ "$PARSING_COMPLETE" = false ]; then
  echo "❌ Parsing timed out."
  exit 1
fi

echo "✅ Parsing complete."

# 4. Validate
echo -e "\nmagnifying_glass Step 4: Validating Extracted Data..."
FAILURES=0

# Name
NAME=$(echo $CANDIDATE_RESP | jq -r '.data.parsed_data.name')
echo "   - Extracted Name: $NAME"

if [ "$NAME" != "null" ] && [ -n "$NAME" ]; then
  echo "✅ Name found"
else
  echo "❌ Name missing"
  FAILURES=$((FAILURES+1))
fi

# Phone
PHONE=$(echo $CANDIDATE_RESP | jq -r '.data.parsed_data.phone')
echo "   - Extracted Phone: $PHONE"
if [ "$PHONE" != "null" ] && [ -n "$PHONE" ]; then
    echo "✅ Phone found"
else
    echo "❌ Phone missing"
    FAILURES=$((FAILURES+1))
fi

# 5. CORS Check
echo -e "\n🌐 Step 5: Verifying CORS on Matches Endpoint..."
# curl -I outputs headers. We grep for Access-Control-Allow-Origin
CORS_HEADERS=$(curl -s -I -H "Origin: http://localhost:3001" -H "Authorization: Bearer $TOKEN" "$BASE_URL/api/matches?candidate_id=$CANDIDATE_ID" | grep -i "Access-Control-Allow-Origin")
COUNT=$(echo "$CORS_HEADERS" | wc -l)

echo -n "   - Access-Control-Allow-Origin Header Count: $COUNT... "

if [ "$COUNT" -eq 1 ]; then
   echo "✅ OK"
else
   echo "❌ FAILED (Expected 1 header, found $COUNT)"
   FAILURES=$((FAILURES+1))
fi

echo -e "\n📊 Verification Summary:"
if [ $FAILURES -eq 0 ]; then
  echo "🎉 ALL CHECKS PASSED!"
  exit 0
else
  echo "💥 $FAILURES CHECKS FAILED!"
  exit 1
fi
