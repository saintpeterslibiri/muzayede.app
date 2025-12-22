const db = require('./config/database');
const fs = require('fs');
const path = require('path');

async function runMigration() {
    try {
        console.log('Starting decimal precision migration (20, 2)...');
        
        const migrationPath = path.join(__dirname, 'data', 'migration_increase_decimal_20.sql');
        const migrationSQL = fs.readFileSync(migrationPath, 'utf8');
        
        const statements = migrationSQL
            .split(';')
            .map(s => s.trim())
            .filter(s => s.length > 0 && !s.startsWith('--'));
        
        for (let i = 0; i < statements.length; i++) {
            const statement = statements[i];
            if (statement) {
                try {
                    await db.query(statement);
                    console.log(`✓ Statement ${i + 1} executed`);
                } catch (error) {
                    console.error(`Error executing statement ${i + 1}: ${statement}`);
                    console.error(error.message);
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
