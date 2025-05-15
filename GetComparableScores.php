<?php

require("database.php");
require("constants.php");

function getScore($player1, $player2, $random) {
    $database = new Database("localhost", DATABASE, MARIAUSER, MARIAPASSWD);

    $query = "SELECT 
                a.map_id,
                a.user_id AS user_player,
                a.score AS score_player,
                a.maxcombo AS maxcombo_player,
                a.perfect AS perfect_player,
                a.date AS date_player,
                a.rank AS rank_player,
                a.enabled_mods AS mods_player,
                b.user_id AS user_target,
                b.score AS score_target,
                b.maxcombo AS maxcombo_target,
                b.perfect AS perfect_target,
                b.date AS date_target,
                b.rank AS rank_target,
                b.enabled_mods AS mods_target
            FROM UserScores a
            JOIN UserScores b ON a.map_id = b.map_id
            WHERE a.user_id = :player2
              AND b.user_id = :player1
              AND a.score < b.score ";

    if($random == 1) {
        $query .= "ORDER BY RAND()
                    LIMIT 1";
    }
            
    $binds = [
        "player1" => $player1,
        "player2" => $player2
    ];

    $result = $database->queryData2($query, $binds);

    if (!$result || count($result) === 0) {
        echo json_encode(["error" => "no mutual beatmap where player1 < player2"], JSON_PRETTY_PRINT);
        return;
    }

    header('Content-Type: application/json; charset=utf-8');

    if($random == 1) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            "map_id" => $result[0]['map_id'],
            "player" => [
                "user_id"    => $result[0]['user_player'],
                "score"      => $result[0]['score_player'],
                "maxcombo"   => $result[0]['maxcombo_player'],
                "perfect"    => $result[0]['perfect_player'],
                "date"       => $result[0]['date_player'],
                "rank"       => $result[0]['rank_player'],
                "mods"       => $result[0]['mods_player']
            ],
            "target" => [
                "user_id"    => $result[0]['user_target'],
                "score"      => $result[0]['score_target'],
                "maxcombo"   => $result[0]['maxcombo_target'],
                "perfect"    => $result[0]['perfect_target'],
                "date"       => $result[0]['date_target'],
                "rank"       => $result[0]['rank_target'],
                "mods"       => $result[0]['mods_target']
            ]
        ], JSON_PRETTY_PRINT);
    } else {
        $originalCount = count($result);
        for ($i = 0; $i < $originalCount; $i++) {
            $output[] = [
                "map_id" => $result[$i]['map_id'],
                "player" => [
                    "user_id"    => $result[$i]['user_player'],
                    "score"      => $result[$i]['score_player'],
                    "maxcombo"   => $result[$i]['maxcombo_player'],
                    "perfect"    => $result[$i]['perfect_player'],
                    "date"       => $result[$i]['date_player'],
                    "rank"       => $result[$i]['rank_player'],
                    "mods"       => $result[$i]['mods_player']
                ],
                "target" => [
                    "user_id"    => $result[$i]['user_target'],
                    "score"      => $result[$i]['score_target'],
                    "maxcombo"   => $result[$i]['maxcombo_target'],
                    "perfect"    => $result[$i]['perfect_target'],
                    "date"       => $result[$i]['date_target'],
                    "rank"       => $result[$i]['rank_target'],
                    "mods"       => $result[$i]['mods_target']
                ]
            ];
        }
        echo json_encode($output, JSON_PRETTY_PRINT);
    }
}