<?php
if (!function_exists("CheckMutualMaps")) {
    function CheckMutualMaps($player1, $player2) {
        require("database.php");

        $database = new Database("localhost", DATABASE, MARIAUSER, MARIAPASSWD);

        $query = "SELECT a.map_id 
                FROM UserMostPlayed a 
                WHERE a.user_id = :player_1 
                    AND a.map_id IN (
                        SELECT b.map_id 
                        FROM UserMostPlayed b 
                        WHERE b.user_id = :player_2
                    )";
        
        $binds = [
            "player_1" => (string)$player1,
            "player_2" => (string)$player2
        ];

        return $database->queryData2($query, $binds);
    }
}