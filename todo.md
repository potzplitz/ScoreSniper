most_played abfragen (https://osu.ppy.sh/users/$userid/beatmapsets/most_played?limit=100&offset=100)
-> in db speichern => userid, scoreid, (maybe noch map infos als json string), recnum => done
-> mit sql maps abfragen die beide spieler gespielt haben => done
-> von diesen maps dann die scores abfragen
-> vergleichen

-------------------------------------

request hanlder

GetUserMostPlayed.php mit sql queue verbinden

sql tabelle (queue)
-> spalten: user_id, state(running, error, waiting), progress(x/y scores completed)

-------------------------------------

scores abfragen

-------------------------------------

flow

erster visit auf seite
GetUserMostPlayed.php -> RequestMutualScores.php => function call auf CheckMutualMaps.hph




------------------------------------
requesthandler.php darf nicht aufgerufen werden sondern muss immer mit einer while true schleife laufen und wenn in status tabelle ein datensatz mit waiting ist, werden diese nach der PID reihenfolge verarbeitet.




-----------------------------


bedingung für scores fertig gefetched

wenn offset bei beiden spielern == anzahl von den zu verarbeitenden maps


--------------------------



möglich machen in den querys, dass wenn die user ids vertauscht sind, auch diese dann noch richtig erkannt werden