const mysql = require('mysql2/promise');
const config = require('../appsettings.json');

// Create connection pool
const pool = mysql.createPool({
    host: config.database.host,
    user: config.database.user,
    password: config.database.password,
    database: config.database.database,
    port: config.database.port || 3306,
    waitForConnections: true,
    connectionLimit: config.database.connectionLimit || 10,
    queueLimit: 0,
    charset: config.database.charset || 'utf8mb4'
});

// Test connection
pool.getConnection()
    .then(connection => {
        console.log('Database connected successfully');
        connection.release();
    })
    .catch(err => {
        console.error('Database connection error:', err);
    });

module.exports = pool;
