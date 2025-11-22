<?php

date_default_timezone_set('Europe/Bucharest');
class Database {
    private static ?Database $instance = null;  
    private PDO $connection;

    
    private string $host = "sql100.infinityfree.com";
    private string $dbName = "if0_40383803_revista_online_db"; 
    private string $username = "if0_40383803";
    private string $password = "j520162016";

    private array $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

   
    private function __clone() {}

   
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }

   
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    
    public function getConnection(): PDO {
        return $this->connection;
    }
}