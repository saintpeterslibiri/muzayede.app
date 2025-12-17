//node.js mySql connection
const mysql = require('mysql2');

const pool =mysql.createPool({
    host:'localhost',
    user:'root',
    password:'',
    database:'muzayede_db',
    port:3306,
    waitForConnections: true,
    connectionLimit: 100,
    //0 = sınırsız
    queueLimit: 0

});
const promisePool =pool.promise();


//when the server start this funtion will calling
//then control the connection of databse 
async function testConnection(){
    try{
        const connection = await promisePool.getConnection();

        console.log('MySql connection successful');
        console.log('database: muzayede_db');
        // release the connection 
        //if not pool will full and connectionsdoes not open
        connection.release();
    }catch(error){
        console.error('MySql connection error:',error.message);

        //common errors
         if (error.code === 'ECONNREFUSED') {
            console.error('   → MySQL may not work. MySQL restart in XAMPP.');
        }
        if (error.code === 'ER_ACCESS_DENIED_ERROR') {
            console.error('   → user or password wrong.');
        }
        if (error.code === 'ER_BAD_DB_ERROR') {
            console.error('   → database dont find. create in phpMyAdmin.');
        }

    }
}

//for using another files

module.exports = {
    pool : promisePool,
    testConnection:testConnection
};