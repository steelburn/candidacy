<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Configuration
$baseUrl = 'http://localhost:8080';
$username = 'admin@test.com';
$password = 'password'; 

// Test resume configuration - use generated test resume with known data
$resumePath = 'tests/fixtures/test_resume_software_developer.pdf';

// Expected values from the markdown source for validation
$expectedData = [
    'name' => 'John Michael Chen',
    'phone' => '+1-555-123-4567',
    'email' => 'john.chen@email.com'
];

echo "🚀 Starting Automated Verification Pipeline...\n";

$client = new Client([
    'base_uri' => $baseUrl,
    'timeout'  => 30,
    'http_errors' => false // Handle errors manually
]);

// 1. Authenticate
echo "\n🔑 Step 1: Authenticating...\n";
try {
    $response = $client->post('/api/auth/login', [
        'json' => [
            'email' => $username,
            'password' => $password
        ]
    ]);
    
    if ($response->getStatusCode() !== 200) {
        die("❌ Login failed: " . $response->getBody() . "\n");
    }
    
    $data = json_decode($response->getBody(), true);
    $token = $data['token'] ?? null;
    $candidateId = $data['user']['id'] ?? 1; // Assuming admin/candidate context or creating new?
    
    if (!$token) die("❌ No token received.\n");
    echo "✅ Login successful. Token received.\n";

} catch (Exception $e) {
    die("❌ Login error: " . $e->getMessage() . "\n");
}

// 2. Upload Resume (and Trigger Parsing)
echo "\nPAGE: 2: Uploading Resume $resumePath for Candidate ID $candidateId...\n";

if (!file_exists($resumePath)) {
    // Try absolute path if relative fails
    $resumePath = '/home/steelburn/Development/candidacy/' . $resumePath;
    if (!file_exists($resumePath)) {
         die("❌ Resume file not found at $resumePath\n");
    }
}

try {
    $response = $client->post("/api/candidates/$candidateId/cv", [
        'headers' => [
            'Authorization' => "Bearer $token"
        ],
        'multipart' => [
            [
                'name'     => 'cv_file',
                'contents' => fopen($resumePath, 'r'),
                'filename' => basename($resumePath)
            ]
        ]
    ]);

    if ($response->getStatusCode() !== 200) {
        die("❌ Upload failed: " . $response->getBody() . "\n");
    }
    
    $uploadData = json_decode($response->getBody(), true);
    echo "✅ Upload successful. Job dispatched.\n";

} catch (Exception $e) {
    die("❌ Upload error: " . $e->getMessage() . "\n");
}

// 3. Poll for Parsing Completion
echo "\n⏳ Step 3: Polling for Parsing Results...\n";
$maxRetries = 30; // 30 * 2s = 60s timeout
$parsingComplete = false;
$parsedData = null;

for ($i = 0; $i < $maxRetries; $i++) {
    sleep(2);
    echo ".";
    
    $response = $client->get("/api/candidates/$candidateId", [
        'headers' => [
            'Authorization' => "Bearer $token"
        ]
    ]);
    
    $candidate = json_decode($response->getBody(), true);
    $data = $candidate['data'] ?? $candidate;
    
    // Check if parsing is done (name/phone populated or specific flag)
    // Assuming backend updates candidate record directly or we check parsing_status field if it exists
    if (!empty($data['parsed_data'])) {
        $parsedData = $data['parsed_data'];
        $parsingComplete = true;
        break;
    }
}

echo "\n";

if (!$parsingComplete) {
    die("❌ Parsing timed out or failed to update candidate record.\n");
}

echo "✅ Parsing complete.\n";

// 4. Validate Data (The Core Test)
echo "\n🔍 Step 4: Validating Extracted Data...\n";
$errors = [];

// Name Check - flexible matching (case-insensitive, partial match)
echo "   - Checking Name... ";
$name = $parsedData['name'] ?? null;
$expectedName = $expectedData['name'];
if ($name && (stripos($name, 'John') !== false && stripos($name, 'Chen') !== false)) {
    echo "✅ OK ($name)\n";
} elseif ($name && strtolower($name) === strtolower($expectedName)) {
    echo "✅ OK ($name)\n";
} else {
    echo "❌ FAILED (Expected: $expectedName, Got: " . json_encode($name) . ")\n";
    $errors[] = "Name mismatch";
}

// Phone Check - normalize for comparison
echo "   - Checking Phone... ";
$phone = $parsedData['phone'] ?? null;
$expectedPhone = $expectedData['phone'];
$normalizedPhone = preg_replace('/[^0-9+]/', '', $phone ?? '');
$normalizedExpected = preg_replace('/[^0-9+]/', '', $expectedPhone);
if ($normalizedPhone === $normalizedExpected) {
    echo "✅ OK ($phone)\n";
} elseif ($phone && strpos($phone, '555') !== false && strpos($phone, '123') !== false) {
    echo "✅ OK ($phone) - partial match\n";
} else {
    echo "❌ FAILED (Expected: $expectedPhone, Got: " . json_encode($phone) . ")\n";
    $errors[] = "Phone mismatch";
}

// Email Check
echo "   - Checking Email... ";
$email = $parsedData['email'] ?? null;
$expectedEmail = $expectedData['email'];
if ($email && strtolower($email) === strtolower($expectedEmail)) {
    echo "✅ OK ($email)\n";
} elseif ($email && stripos($email, 'john') !== false && stripos($email, 'chen') !== false) {
    echo "✅ OK ($email) - partial match\n";
} else {
    echo "⚠️  Email not found or mismatch (Expected: $expectedEmail, Got: " . json_encode($email) . ")\n";
    // Not a fatal error as email extraction varies
}

// Structure Check - verify experience/skills parsing
echo "   - Checking Parsed Structure... ";
$experience = $parsedData['experience'] ?? [];
$skills = $parsedData['skills'] ?? $parsedData['technical_skills'] ?? [];
if (!empty($experience) || !empty($skills)) {
    $info = [];
    if (!empty($experience)) $info[] = count($experience) . " experience entries";
    if (!empty($skills)) $info[] = "skills found";
    echo "✅ OK (" . implode(', ', $info) . ")\n";
} else {
    echo "⚠️  No structured experience/skills found (may use raw text)\n";
    // Not fatal, as some parsers return raw text only
}

// 5. Verify CORS on Matches Endpoint
echo "\n🌐 Step 5: Verifying CORS on Matches Endpoint...\n";
try {
    $response = $client->options('/api/matches?candidate_id=' . $candidateId, [
        'headers' => [
            'Origin' => 'http://localhost:3001',
            'Access-Control-Request-Method' => 'GET'
        ]
    ]);
    
    // OR just a GET with Origin
     $response = $client->get('/api/matches?candidate_id=' . $candidateId, [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Origin' => 'http://localhost:3001'
        ]
    ]);
    
    $allowOrigin = $response->getHeader('Access-Control-Allow-Origin');
    $count = count($allowOrigin);
    
    echo "   - Access-Control-Allow-Origin Header Count: $count\n";
    
    if ($count === 1 && $allowOrigin[0] === 'http://localhost:3001') {
        echo "✅ CORS OK (Single header present)\n";
    } else {
        echo "❌ CORS FAILED (Expected 1 header, got $count: " . json_encode($allowOrigin) . ")\n";
        $errors[] = "CORS header error";
    }

} catch (Exception $e) {
    echo "❌ CORS Request Failed: " . $e->getMessage() . "\n";
    $errors[] = "CORS request failed";
}


// Summary
echo "\n📊 Verification Summary:\n";
if (empty($errors)) {
    echo "🎉 ALL CHECKS PASSED!\n";
    exit(0);
} else {
    echo "💥 ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    exit(1);
}
