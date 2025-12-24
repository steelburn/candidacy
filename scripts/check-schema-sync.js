#!/usr/bin/env node

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🔍 Checking schema sync status...\n');

try {
    // 1. Generate fresh SQL from DBML
    console.log('🔨 Generating temporary SQL from DBML...');
    const dbmlSql = execSync('dbml2sql schema.dbml --mysql', { encoding: 'utf8' });

    // 2. Check if database/schema.sql is up to date
    const schemaFile = path.join(__dirname, '..', 'database', 'schema.sql');
    if (fs.existsSync(schemaFile)) {
        const existingSql = fs.readFileSync(schemaFile, 'utf8');
        if (dbmlSql.trim() !== existingSql.trim()) {
            console.warn('⚠️  Warning: database/schema.sql is out of sync with schema.dbml');
            console.warn('   Run "make dbml-sql" to update it.');
        } else {
            console.log('✅ database/schema.sql is in sync.');
        }
    } else {
        console.warn('⚠️  Warning: database/schema.sql does not exist.');
    }

    // 3. (Optional) Check against live databases if containers are running
    // This is more complex and depends on docker being up.
    // For now, we'll focus on the DBML -> SQL sync.

    console.log('\n✨ Sync check complete.');
} catch (error) {
    console.error('❌ Error during sync check:', error.message);
    process.exit(1);
}
