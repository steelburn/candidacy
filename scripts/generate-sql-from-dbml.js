#!/usr/bin/env node

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🔨 Generating SQL from DBML...\n');

// Service to database mapping
const services = {
    'auth-service': 'candidacy_auth',
    'candidate-service': 'candidacy_candidate',
    'vacancy-service': 'candidacy_vacancy',
    'matching-service': 'candidacy_matching',
    'interview-service': 'candidacy_interview',
    'offer-service': 'candidacy_offer',
    'onboarding-service': 'candidacy_onboarding',
    'admin-service': 'candidacy_admin',
    'document-parser-service': 'candidacy_document_parser'
};

// Create output directory
const sqlDir = path.join(__dirname, '..', 'database', 'sql');
if (!fs.existsSync(sqlDir)) {
    fs.mkdirSync(sqlDir, { recursive: true });
}

try {
    // Generate complete SQL from DBML
    console.log('📄 Generating complete SQL schema...');
    const fullSQL = execSync('dbml2sql schema.dbml --mysql', { encoding: 'utf8' });

    // Save complete SQL
    const schemaPath = path.join(__dirname, '..', 'database', 'schema.sql');
    fs.writeFileSync(schemaPath, fullSQL);
    console.log(`✅ Generated ${schemaPath}\n`);

    // Split SQL by database
    console.log('📊 Splitting SQL by service database...');
    const databases = splitSQLByDatabase(fullSQL, services);

    // Write per-service SQL files
    for (const [dbName, sql] of Object.entries(databases)) {
        const filePath = path.join(sqlDir, `${dbName}.sql`);
        fs.writeFileSync(filePath, sql);
        console.log(`  ✅ ${dbName}.sql`);
    }

    console.log('\n✅ SQL generation complete!');
    console.log(`   Generated ${Object.keys(databases).length} service SQL files`);

} catch (error) {
    console.error('❌ Error generating SQL:', error.message);
    process.exit(1);
}

function splitSQLByDatabase(sql, services) {
    const databases = {};

    // Initialize each database with empty string
    Object.values(services).forEach(dbName => {
        databases[dbName] = '';
    });

    // Split SQL into statements
    const statements = sql.split(';').filter(s => s.trim());

    for (const statement of statements) {
        const trimmed = statement.trim();
        if (!trimmed) continue;

        // Determine which database this statement belongs to
        let targetDb = null;

        // Check table names in CREATE TABLE, ALTER TABLE, CREATE INDEX statements
        const createTableMatch = trimmed.match(/CREATE TABLE [`"]?(\w+)[`"]?/i);
        const alterTableMatch = trimmed.match(/ALTER TABLE [`"]?(\w+)[`"]?/i);
        const createIndexMatch = trimmed.match(/CREATE (?:UNIQUE )?INDEX .+ ON [`"]?(\w+)[`"]?/i);

        const tableName = createTableMatch?.[1] || alterTableMatch?.[1] || createIndexMatch?.[1];

        if (tableName) {
            targetDb = getDatabaseForTable(tableName, services);
        }

        // Add statement to appropriate database
        if (targetDb && databases[targetDb] !== undefined) {
            databases[targetDb] += trimmed + ';\n\n';
        }
    }

    return databases;
}

function getDatabaseForTable(tableName, services) {
    // Map table names to databases based on schema.dbml structure
    const tableToDatabase = {
        // Auth service
        'users': 'candidacy_auth',
        'roles': 'candidacy_auth',
        'permissions': 'candidacy_auth',
        'role_user': 'candidacy_auth',
        'permission_role': 'candidacy_auth',
        'password_reset_tokens': 'candidacy_auth',
        'personal_access_tokens': 'candidacy_auth',
        'failed_jobs': 'candidacy_auth',

        // Candidate service
        'candidates': 'candidacy_candidate',
        'cv_files': 'candidacy_candidate',
        'candidate_tokens': 'candidacy_candidate',
        'applicant_answers': 'candidacy_candidate',
        'job_statuses': 'candidacy_candidate',

        // Vacancy service
        'vacancies': 'candidacy_vacancy',
        'vacancy_questions': 'candidacy_vacancy',
        'required_skills': 'candidacy_vacancy',

        // Matching service
        'matches': 'candidacy_matching',
        'job_statuses_matching': 'candidacy_matching',

        // Interview service
        'interviews': 'candidacy_interview',
        'interview_feedback': 'candidacy_interview',

        // Offer service
        'offers': 'candidacy_offer',

        // Onboarding service
        'onboarding_checklists': 'candidacy_onboarding',

        // Admin service
        'settings': 'candidacy_admin',

        // Document parser service
        'parse_jobs': 'candidacy_document_parser'
    };

    return tableToDatabase[tableName] || null;
}
