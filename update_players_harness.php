<?php

date_default_timezone_set('America/New_York');

include "includes/update_players.php";

include ".env.php";

$season = date("Y");

$today = date("z");

include "includes/get_dbconn.php";

$dbconn = get_dbconn();

/*************************************************************/

update_players($dbconn, $season, $today);