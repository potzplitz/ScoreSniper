<?php
require("database.php");
require("CheckMutualMaps.php");
require("constants.php");

if (!function_exists("getPlays")) {
    function getScores() {
        global $database;

        $queryQueue = "SELECT * FROM queueUserScores WHERE state != 'finished' ORDER BY queue_id ASC";
        $queue = $database->queryData($queryQueue);

        while (count(array_filter($queue, fn($q) => $q['state'] !== 'finished')) > 0) {
            for ($i = 0; $i < count($queue); $i++) {
                $players = [$queue[$i]['user_id1'], $queue[$i]['user_id2']];
                $offsets = [
                    $queue[$i]['offset_player1'], 
                    $queue[$i]['offset_player2']
                ];

                $offsets[0]++;
                $offsets[1]++;
                
                $updateStatusQ = "UPDATE status set value3 = 'processing_scores', process_description = 'Fetching scores for both users' where value1 = :value1 and value2 = :value2";
                $updateStatusB = [
                    "value1" => $players[0],
                    "value2" => $players[1]
                ];
                $database->queryData2($updateStatusQ, $updateStatusB);

                $maps = CheckMutualMaps($players[0], $players[1]);

                $queryStatusU = "UPDATE queueUserScores set state = 'processing', progress = :progress, offset_player1 = :offset_player1, offset_player2 = :offset_player2 where user_id1 = :value1 and user_id2 = :value2";
                $bindsStatusU = [
                    "value1" => $players[0],
                    "value2" => $players[1],
                    "offset_player1" => $offsets[0],
                    "offset_player2" => $offsets[1],
                    "progress" => $offsets[1] . " / " . count($maps)
                ];
                $database->queryData2($queryStatusU, $bindsStatusU);

                if (isset($maps[$offsets[0]])) {
                    requestScoresAndWriteToDB($players[0], $maps[$offsets[0]]['map_id']);
                }

                if (isset($maps[$offsets[1]])) {
                    requestScoresAndWriteToDB($players[1], $maps[$offsets[1]]['map_id']);
                }

                if (($offsets[0] >= count($maps)) && ($offsets[1] >= count($maps))) {
                    $setFinishedStatusQ = "UPDATE queueUserScores SET state = 'finished' WHERE user_id1 = :user_id1 AND user_id2 = :user_id2";
                    $setFinishedStatusB = [
                        "user_id1" => $players[0],
                        "user_id2" => $players[1],
                    ];
                    $database->queryData2($setFinishedStatusQ, $setFinishedStatusB);

                    $deleteFinished = "DELETE FROM queueMostPlayed WHERE user_id1 = :user_id1 AND user_id2 = :user_id2";
                    $deleteBinds = [
                        "user_id1" => $players[0],
                        "user_id2" => $players[1]
                    ];
                    $database->queryData2($deleteFinished, $deleteBinds);

                    $updateStatus = "UPDATE status set value3 = :value3, process_description = 'finished fetching scores on mutual beatmaps' where value1 = :value1 and value2 = :value2";
                    $BindsStatusU = [
                        "value3" => "finished",
                        "value1" => $players[0],
                        "value2" => $players[1]
                    ];
            
                    $database->queryData2($updateStatus, $BindsStatusU);

                    echo "Beide Spieler fertig, Queue-Eintrag abgeschlossen.\n";
                    continue;
                }
            }

            $queue = $database->queryData($queryQueue);
        }
    }
}

if(!function_exists("requestScoresAndWriteToDB")) {
    function requestScoresAndWriteToDB($userid, $map) {
        global $database;

        sleep(1);
        $score_url = "https://osu.ppy.sh/api/get_scores?k=" . OSUKEY . "&b=" . $map . "&u=" . $userid;

        $response = @file_get_contents($score_url);
        $ParsedResponse = json_decode($response, true);

        if (!isset($ParsedResponse[0]['score_id'])) {
            echo "score nicht gefunden \n";
            return;
        }

        $score_id = (int)$ParsedResponse[0]['score_id'];

        $checkQuery = "SELECT COUNT(*) AS cnt FROM UserScores WHERE score_id = :score_id";
        $checkBinds = ['score_id' => $score_id];
        $checkResult = $database->queryData2($checkQuery, $checkBinds);
        $exists = isset($checkResult[0]['cnt']) && $checkResult[0]['cnt'] > 0;

        if ($exists) {
            echo "score $score_id existiert schon\n";
            return;
        }

        $query = "INSERT INTO UserScores 
            (score_id, score, maxcombo, user_id, perfect, date, rank, enabled_mods, map_id) 
        VALUES 
            (:score_id, :score, :maxcombo, :user_id, :perfect, :date, :rank, :enabled_mods, :map_id)
        ON DUPLICATE KEY UPDATE
            score = VALUES(score),
            maxcombo = VALUES(maxcombo),
            user_id = VALUES(user_id),
            perfect = VALUES(perfect),
            date = VALUES(date),
            rank = VALUES(rank),
            enabled_mods = VALUES(enabled_mods),
            map_id = VALUES(map_id)";


        $binds = [
            "score_id"     => $score_id,
            "score"        => (int)$ParsedResponse[0]['score'],
            "maxcombo"     => (int)$ParsedResponse[0]['maxcombo'],
            "user_id"      => (int)$ParsedResponse[0]['user_id'],
            "perfect"      => (int)$ParsedResponse[0]['perfect'],
            "date"         => $ParsedResponse[0]['date'],
            "rank"         => $ParsedResponse[0]['rank'],
            "enabled_mods" => (int)$ParsedResponse[0]['enabled_mods'],
            "map_id"       => (int)$map
        ];

        $database->queryData2($query, $binds);
        echo "score $score_id eingefügt\n";
    }
}