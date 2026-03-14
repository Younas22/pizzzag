<?php
/**
 * Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'pizzahubkk');  // apna database name dalo
define('DB_USER', 'root');        // apna username dalo
define('DB_PASS', '');            // apna password dalo


// define('DB_HOST', 'localhost');
// define('DB_NAME', 'u957225996_pizzahubkk');  // apna database name dalo
// define('DB_USER', 'u957225996_pizzahubkk');        // apna username dalo
// define('DB_PASS', 'U957225996_pizzahubkk');            // apna password dalo

function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}
