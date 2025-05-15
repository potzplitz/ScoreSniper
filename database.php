<?php

if(!class_exists("Database")) {
    class Database {
        public $pdo;
        private $rows;

        public function __construct($host, $dbname, $user, $psswd) {

            try {

                $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
                $this->pdo = new PDO($dsn, $user, (!isset($psswd)) ? "" : $psswd);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch (PDOException $e) {

                echo "Verbindung fehlgeschlagen.";
            }
        }
        
        public function rows(): int { // returns count of rows of select in current instance
           return $this->rows;
        }

        public function queryData($query) { // deprecated
            $stmt = $this->pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function queryData2($query, $binds) { // prepared
            $stmt = $this->pdo->prepare($query);

            foreach ($binds as $param => $value) {
                $stmt->bindValue($param, $value);
            }

            $stmt->execute();
            $this->rows = $stmt->rowCount();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }    
    }  
}

?>
