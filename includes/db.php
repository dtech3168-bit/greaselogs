<?php
/**
* Greaselogs - Database Connection (SINGLE SOURCE)
*/

function db() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
        } catch (PDOException $e) {
            error_log("DB Connection Failed: " . $e->getMessage());
            http_response_code(500);
            if (defined('APP_ENV') && APP_ENV !== 'production') {
                die("DB Connection Failed: " . $e->getMessage());
            }
            die("We're having trouble connecting right now. Please try again shortly.");
        }
    }

    return $pdo;
} 
