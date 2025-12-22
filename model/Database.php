<?php

date_default_timezone_set('Europe/Bucharest');

class Database {
    private static ?Database $instance = null;  
    private PDO $connection;

    // Definim variabilele, dar le completam in constructor
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;

    private function __construct() {
        // === LOGICA AUTOMATA DE DETECTIE ===
        // Verificam daca site-ul ruleaza pe calculatorul tau (localhost)
        if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
            // ---> AICI SUNT SETARILE PENTRU ACASA (XAMPP)
            $this->host = "localhost";
            $this->username = "root";
            $this->password = ""; 
            $this->dbName = "revista_online_db"; // Numele bazei tale locale
        } else {
            // ---> AICI SUNT SETARILE PENTRU INFINITYFREE (LIVE)
            // Am pus datele tale exacte aici:
            $this->host = "sql100.infinityfree.com";
            $this->username = "if0_40383803";
            $this->password = "j520162016"; // Parola ta este pusa aici
            $this->dbName = "if0_40383803_revista_online_db";
        }

        // Conectarea efectiva la baza de date
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
            
            $this->connection = new PDO($dsn, $this->username, $this->password);
            
            // Optiuni standard pentru performanta si erori
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // Singleton Pattern - previne clonarea
    private function __clone() {}

    // Singleton Pattern - previne deserializarea
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }

    // Returneaza instanta unica a clasei
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Returneaza conexiunea PDO activa
    public function getConnection(): PDO {
        return $this->connection;
    }
}