<?php
class Database {
    private static $db;
    public static function getConnection() {
        if (!self::$db) {
            // Pfad absolut zum Container
            self::$db = new PDO('sqlite:/var/www/html/storage/database.sqlite');
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$db->exec("CREATE TABLE IF NOT EXISTS secrets (
                id TEXT PRIMARY KEY, 
                filename TEXT, 
                expires_at INTEGER, 
                is_file INTEGER
            )");
        }
        return self::$db;
    }
}