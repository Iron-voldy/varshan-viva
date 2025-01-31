<?php

class Database {
    public static $connection = null;

    public static function setUpConnection() {
        if (!isset(self::$connection)) {
            self::$connection = new mysqli("localhost", "root", "2009928", "CareCompass", 3306);
            if (self::$connection->connect_error) {
                die("Database connection failed: " . self::$connection->connect_error);
            }
        }
    }

    public static function iud($q) {
        self::setUpConnection();
        return self::$connection->query($q);
    }

    public static function search($q) {
        self::setUpConnection();
        return self::$connection->query($q);
    }
}

// Ensure connection is established when the file is included
Database::setUpConnection();

?>
