const db = require('./config/database');
const fs = require('fs');
const path = require('path');

async function runMigration() {
    try {
        console.log('Starting image migration...');
        
        const migrationPath = path.join(__dirname, 'data', 'migration_images_db.sql');
        const migrationSQL = fs.readFileSync(migrationPath, 'utf8');
        
        const statements = migrationSQL
            .split(';')
            .map(s => s.trim())
            .filter(s => s.length > 0);
        
        for (let i = 0; i < statements.length; i++) {
            const statement = statements[i];
            if (statement) {
                try {
                    await db.query(statement);
                    console.log(`✓ Statement ${i + 1} executed`);
                } catch (error) {
                    if (error.code === 'ER_DUP_FIELDNAME') {
                        console.log(`⚠ Column already exists, skipping.`);
                    } else {
                        console.error(`Error executing statement: ${statement}`);
                        throw error;
                    }
                }
            }
        }
        
        console.log('Migration completed.');
        process.exit(0);
    } catch (error) {
        console.error('Migration failed:', error);
        process.exit(1);
    }
}

runMigration();
