<?php

require("database.php");
require("GetUserMostPlayed.php");

$database = new Database("localhost", DATABASE, MARIAUSER, MARIAPASSWD);

while(true) {

    $queryQueue = "INSERT into queueMostPlayed (user_id1, user_id2, state, progress, offset_player1, offset_player2) select value1, value2, 'waiting', '0', 0, 0 from status where value3 = 'waiting'";
    $database->queryData($queryQueue);

    $queryGetNew = "SELECT * from queueMostPlayed where state = 'waiting' order by queue_id asc";
    $waitingUsers = $database->queryData($queryGetNew);

    if($waitingUsers != null) {
        getPlays();
    }
    sleep(1);
}