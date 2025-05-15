<?php

require("database.php");
require("constants.php");
require("GetComparableScores.php");

$database = new Database("localhost", DATABASE, MARIAUSER, MARIAPASSWD);

$player1 = $_GET['target'];
$player2 = $_GET['player'];
$random  = $_GET['random'] ?? 0;

$queryStatus = "SELECT * from status where ((value1 = :value1 and value2 = :value2) or (value1 = :value2 and value2 = :value1))";
$bindsStatus = [
    "value1" => $player1,
    "value2" => $player2
];

$status = $database->queryData2($queryStatus, $bindsStatus);

if($status[0]['value3'] == 'finished' || $status[0]['value3'] == 'processing_scores') {
    getScore($player1, $player2, $random);

} else if($status[0]['value3'] == 'processing_maps') {

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "processing", "state" => $status[0]['process_description']]);

} else if($status[0]['value3'] == 'waiting')  {
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "processing", "state" => $status[0]['process_description']]);

} else {

    $query = "SELECT (select count(1) from UserScores where user_id = :player1) as exist_user1, (select count(1) from UserScores where user_id = :player2) as exist_user2";
    $binds = [
        "player1" => $player1,
        "player2" => $player2
    ];
    
    $status = $database->queryData2($query, $binds);
    
    if($status[0]['exist_user1'] <= 0 || $status[0]['exist_user2'] <= 0) {
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["message" => "User(s) not found, starting to fetch mutual scores"]);
    
        $queryStatus = "INSERT into status (process_name, process_description, value1, value2, value3) values ('fetch_scores', :description, :value1, :value2, :value3)";
        $binds = [
            "description" => "Waiting in Queue",
            "value1" => (string)$player1,
            "value2" => (string)$player2,
            "value3" => "waiting"
        ];
    
        $database->queryData2($queryStatus, $binds);

    }    
}