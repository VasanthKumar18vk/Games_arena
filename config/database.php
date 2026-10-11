<?php
/**
 * Games Arena - Database Connection (PDO)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static ?string $lastError = null;

    public static function getConnection(): ?PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                self::$lastError = $e->getMessage();
                // If database does not exist, attempt connection without dbname for auto-setup
                if (strpos(strtolower($e->getMessage()), 'unknown database') !== false) {
                    try {
                        $rawDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                        $rawPdo = new PDO($rawDsn, DB_USER, DB_PASS, $options);
                        return $rawPdo;
                    } catch (PDOException $ex) {
                        self::$lastError = $ex->getMessage();
                        return null;
                    }
                }
                return null;
            }
        }
        return self::$instance;
    }

    public static function getLastError(): ?string {
        return self::$lastError;
    }

    public static function isConnected(): bool {
        try {
            $conn = self::getConnection();
            if (!$conn) return false;
            $conn->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Global PDO helper
 */
function get_db(): ?PDO {
    return Database::getConnection();
}
