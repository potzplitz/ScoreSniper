<?php

require("database.php");
require("RequestMutualScores.php");

$database = new Database("localhost", DATABASE, MARIAUSER, MARIAPASSWD);

while(true) {

    $queryQueue = "INSERT into queueUserScores (user_id1, user_id2, state, progress, offset_player1, offset_player2) select user_id1, user_id2, 'waiting', '0', 0, 0 from queueMostPlayed where state = 'finished'";
    $database->queryData($queryQueue);

    $queryGetNew = "SELECT * from queueUserScores where state = 'waiting' order by queue_id asc";
    $waitingUsers = $database->queryData($queryGetNew);

    if($waitingUsers != null) {
        getScores();
    }
    sleep(1);
}