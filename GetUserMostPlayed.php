<?php
require("database.php");
require("constants.php");

$database = new Database("localhost", DATABASE, MARIAUSER, MARIAPASSWD);

if (!function_exists("getPlays")) {
    function getPlays() {
        global $database;

        $queryQueue = "SELECT * FROM queueMostPlayed WHERE state != 'finished' ORDER BY queue_id ASC";
        $queue = $database->queryData($queryQueue);

        while (count(array_filter($queue, fn($q) => $q['state'] !== 'finished')) > 0) {
            for ($i = 0; $i < count($queue); $i++) {
                $players = [$queue[$i]['user_id1'], $queue[$i]['user_id2']];
                $offsets = [
                    $queue[$i]['offset_player1'], 
                    $queue[$i]['offset_player2']
                ];

                $updateStatus = "UPDATE status set process_description = :process_description, value3 = :value3 where value1 = :value1 and value2 = :value2";
                $BindsStatusU = [
                    "value3" => "processing_maps",
                    "value1" => $players[0],
                    "value2" => $players[1],
                    "process_description" => "Fetching most played Beatmaps for user " . $players[0] . " and " . $players[1]
                ];
        
                $database->queryData2($updateStatus, $BindsStatusU);

                $queryStatusU = "UPDATE queueMostPlayed set state = 'processing' where user_id1 = :value1 and user_id2 = :value2";
                $bindsStatusU = [
                    "value1" => $players[0],
                    "value2" => $players[1]
                ];

                $database->queryData2($queryStatusU, $bindsStatusU);

                $done1 = requestMapAndWriteToDB($players, $offsets, 0);
                $done2 = requestMapAndWriteToDB($players, $offsets, 1);

                if ($done1 === false && $done2 === false) {
                    $setFinishedStatusQ = "UPDATE queueMostPlayed SET state = 'finished' WHERE user_id1 = :user_id1 AND user_id2 = :user_id2";
                    $setFinishedStatusB = [
                        "user_id1" => $players[0],
                        "user_id2" => $players[1]
                    ];
                    $database->queryData2($setFinishedStatusQ, $setFinishedStatusB);

                    $updateStatus = "UPDATE status set process_description = 'Fetching most played maps from both users finished', value3 = :value3 where value1 = :value1 and value2 = :value2";
                    $BindsStatusU = [
                        "value3" => "finished",
                        "value1" => $players[0],
                        "value2" => $players[1]
                    ];
            
                    $database->queryData2($updateStatus, $BindsStatusU);
                    echo "Beide Spieler fertig, Queue-Eintrag abgeschlossen.\n";
                }
            }

            $queue = $database->queryData($queryQueue);
        }
    }
}
    
if (!function_exists("requestMapAndWriteToDB")) {
    function requestMapAndWriteToDB($players, &$offsets, $player) {
        global $database;

        $score_url = "https://osu.ppy.sh/users/" . $players[$player] . "/beatmapsets/most_played?limit=100&offset=" . $offsets[$player];

        $response = @file_get_contents($score_url);
        $ParsedResponse = json_decode($response, true);

        if (empty($ParsedResponse) || !is_array($ParsedResponse)) {
            echo "Keine weiteren Maps für Spieler " . $players[$player] . "\n";
            return false;
        }

        $offsets[$player] += count($ParsedResponse);

        $queueUpdate = "UPDATE queueMostPlayed 
            SET offset_player1 = :offset_player1, offset_player2 = :offset_player2 
            WHERE user_id1 = :user_id1 AND user_id2 = :user_id2";
        $bindsQueue = [
            "offset_player1" => $offsets[0],
            "offset_player2" => $offsets[1],
            "user_id1"       => $players[0],
            "user_id2"       => $players[1]
        ];
        $database->queryData2($queueUpdate, $bindsQueue);

        $insertMapsQ = "INSERT INTO UserMostPlayed (user_id, map_id) VALUES (:user_id, :map_id)";
        foreach ($ParsedResponse as $entry) {
            $insertMapsB = [
                "user_id" => $players[$player],
                "map_id"  => $entry['beatmap_id']
            ];
            $database->queryData2($insertMapsQ, $insertMapsB);
        }

        $maps = array_column($ParsedResponse, "beatmap_id");

        sleep(1);
        return true;
    }
}