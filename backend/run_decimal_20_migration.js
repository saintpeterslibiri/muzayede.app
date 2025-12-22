const db = require('./config/database');
const fs = require('fs');
const path = require('path');

async function runMigration() {
    try {
        console.log('Starting decimal precision (20,2) migration...');
        
        const migrationPath = path.join(__dirname, 'data', 'migration_increase_decimal_20.sql');
        
        if (!fs.existsSync(migrationPath)) {
            console.error('Migration file not found:', migrationPath);
            process.exit(1);
        }

        const migrationSQL = fs.readFileSync(migrationPath, 'utf8');
        
        const statements = migrationSQL
            .split(';')
            .map(s => s.trim())
            .filter(s => s.length > 0 && !s.startsWith('--'));
        
        console.log(`Found ${statements.length} statements to execute.`);

        for (let i = 0; i < statements.length; i++) {
            const statement = statements[i];
            if (statement) {
                try {
                    console.log(`Executing statement ${i + 1}...`);
                    await db.query(statement);
                    console.log(`✓ Statement ${i + 1} executed`);
                } catch (error) {
                    console.error(`Error executing statement ${i + 1}:`);
                    console.error(statement);
                    console.error('Error message:', error.message);
                    // We continue because some columns might already be updated or not exist
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
