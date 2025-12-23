const mysql = require('mysql2/promise');
const config = require('../appsettings.json');

// Environment variables override config file
const dbConfig = {
    host: process.env.DB_HOST || config.database.host,
    user: process.env.DB_USER || config.database.user,
    password: process.env.DB_PASSWORD || config.database.password,
    database: process.env.DB_NAME || config.database.database,
    port: parseInt(process.env.DB_PORT || config.database.port || 3306),
    waitForConnections: true,
    connectionLimit: config.database.connectionLimit || 10,
    queueLimit: 0,
    charset: config.database.charset || 'utf8mb4',
    timezone: 'Z' // Use UTC timezone to avoid timezone issues
};

// Create connection pool
const pool = mysql.createPool(dbConfig);

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
