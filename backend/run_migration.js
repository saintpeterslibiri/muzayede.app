// =====================================================
// RUN MIGRATION - Execute database migration
// =====================================================

const db = require('./config/database');
const fs = require('fs');
const path = require('path');

async function runMigration() {
    try {
        console.log('Starting migration...');
        
        // Read migration file
        const migrationPath = path.join(__dirname, 'data', '20251812_2330.sql');
        const migrationSQL = fs.readFileSync(migrationPath, 'utf8');
        
        // Split by semicolons but preserve multi-line statements
        // We'll execute the entire file as is
        const statements = migrationSQL
            .split(';')
            .map(s => s.trim())
            .filter(s => s.length > 0 && !s.startsWith('--'));
        
        console.log(`Found ${statements.length} SQL statements to execute`);
        
        // Execute each statement
        for (let i = 0; i < statements.length; i++) {
            const statement = statements[i];
            if (statement.trim()) {
                try {
                    console.log(`Executing statement ${i + 1}/${statements.length}...`);
                    await db.query(statement);
                    console.log(`✓ Statement ${i + 1} executed successfully`);
                } catch (error) {
                    // If error is about column already existing or not existing, continue
                    if (error.code === 'ER_DUP_FIELDNAME' || 
                        error.code === 'ER_BAD_FIELD_ERROR' ||
                        error.sqlMessage?.includes('Duplicate column name') ||
                        error.sqlMessage?.includes('does not exist')) {
                        console.log(`⚠ Statement ${i + 1} skipped: ${error.sqlMessage}`);
                    } else {
                        throw error;
                    }
                }
            }
        }
        
        console.log('\n✓ Migration completed successfully!');
        process.exit(0);
        
    } catch (error) {
        console.error('\n✗ Migration failed:', error);
        process.exit(1);
    }
}

runMigration();

