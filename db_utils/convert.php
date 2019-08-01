<?php

if (file_exists('/Applications/MAMP/htdocs')) {
	$base_path = '/Applications/MAMP/htdocs';
}
else {
	$base_path = '/var/www/html';
}

$dir = "baseball_update_stats";

define("WEB_HOME", "/" . $dir);

define("BASE_PATH", $base_path . WEB_HOME);
define("INCLUDES_PATH", BASE_PATH . "/includes");

set_include_path(INCLUDES_PATH);

define("HTML_PATH", BASE_PATH . "/html");

define("VIEWS", WEB_HOME . "/views");

include INCLUDES_PATH . '/get_dbconn.php';
include INCLUDES_PATH . '/get_all_owners_for_year.php';
include INCLUDES_PATH . '/get_all_players_for_year.php';
include INCLUDES_PATH . '/get_all_teams_html.php';
include INCLUDES_PATH . '/get_owner_drop_down_list.php';
include INCLUDES_PATH . '/get_owner_rows_html.php';
include INCLUDES_PATH . '/get_owner_stats_table.php';
include INCLUDES_PATH . '/get_player_stats_table.php';

include INCLUDES_PATH . '/ordinalSuffix.php';

$GLOBALS["this_year"] = date("Y");

$dbconn = get_dbconn();

$season = date("Y");

$GLOBALS["season_under_way"] = true;

/*********************************************************************/

$GLOBALS["C"]["points_col"] = "Catcher_Points";
$GLOBALS["1B"]["points_col"] = "FirstBase_Points";
$GLOBALS["2B"]["points_col"] = "SecondBase_Points";
$GLOBALS["3B"]["points_col"] = "ThirdBase_Points";
$GLOBALS["SS"]["points_col"] = "Shortstop_Points";
$GLOBALS["RP"]["points_col"] = "RP_Points";

$GLOBALS["roster_active"]["C"]["col_base"] = "Catcher";
$GLOBALS["roster_active"]["C"]["col_name"] = "Catcher_ID";
$GLOBALS["roster_active"]["C"]["points_col"] = "Catcher_Points";
$GLOBALS["roster_active"]["C"]["start_col"] = "Catcher_Start";
$GLOBALS["roster_active"]["C"]["pos"] = "C";

$GLOBALS["roster_active"]["1B"]["col_base"] = "FirstBase";
$GLOBALS["roster_active"]["1B"]["col_name"] = "FirstBase_ID";
$GLOBALS["roster_active"]["1B"]["points_col"] = "FirstBase_Points";
$GLOBALS["roster_active"]["1B"]["start_col"] = "FirstBase_Start";
$GLOBALS["roster_active"]["1B"]["pos"] = "1B";

$GLOBALS["roster_active"]["2B"]["col_base"] = "SecondBase";
$GLOBALS["roster_active"]["2B"]["col_name"] = "SecondBase_ID";
$GLOBALS["roster_active"]["2B"]["points_col"] = "SecondBase_Points";
$GLOBALS["roster_active"]["2B"]["start_col"] = "SecondBase_Start";
$GLOBALS["roster_active"]["2B"]["pos"] = "2B";

$GLOBALS["roster_active"]["3B"]["col_base"] = "ThirdBase";
$GLOBALS["roster_active"]["3B"]["col_name"] = "ThirdBase_ID";
$GLOBALS["roster_active"]["3B"]["points_col"] = "ThirdBase_Points";
$GLOBALS["roster_active"]["3B"]["start_col"] = "ThirdBase_Start";
$GLOBALS["roster_active"]["3B"]["pos"] = "3B";

$GLOBALS["roster_active"]["SS"]["col_base"] = "Shortstop";
$GLOBALS["roster_active"]["SS"]["col_name"] = "Shortstop_ID";
$GLOBALS["roster_active"]["SS"]["points_col"] = "Shortstop_Points";
$GLOBALS["roster_active"]["SS"]["start_col"] = "Shortstop_Start";
$GLOBALS["roster_active"]["SS"]["pos"] = "SS";

$GLOBALS["roster_active"]["OF1"]["col_base"] = "OF1";
$GLOBALS["roster_active"]["OF1"]["col_name"] = "OF1_ID";
$GLOBALS["roster_active"]["OF1"]["points_col"] = "OF1_Points";
$GLOBALS["roster_active"]["OF1"]["start_col"] = "OF1_Start";
$GLOBALS["roster_active"]["OF1"]["pos"] = "OF";

$GLOBALS["roster_active"]["OF2"]["col_base"] = "OF2";
$GLOBALS["roster_active"]["OF2"]["col_name"] = "OF2_ID";
$GLOBALS["roster_active"]["OF2"]["points_col"] = "OF2_Points";
$GLOBALS["roster_active"]["OF2"]["start_col"] = "OF2_Start";
$GLOBALS["roster_active"]["OF2"]["pos"] = "OF";

$GLOBALS["roster_active"]["OF3"]["col_base"] = "OF3";
$GLOBALS["roster_active"]["OF3"]["col_name"] = "OF3_ID";
$GLOBALS["roster_active"]["OF3"]["points_col"] = "OF3_Points";
$GLOBALS["roster_active"]["OF3"]["start_col"] = "OF3_Start";
$GLOBALS["roster_active"]["OF3"]["pos"] = "OF";

$GLOBALS["roster_active"]["SP1"]["col_base"] = "SP1";
$GLOBALS["roster_active"]["SP1"]["col_name"] = "SP1_ID";
$GLOBALS["roster_active"]["SP1"]["points_col"] = "SP1_Points";
$GLOBALS["roster_active"]["SP1"]["start_col"] = "SP1_Start";
$GLOBALS["roster_active"]["SP1"]["pos"] = "SP";

$GLOBALS["roster_active"]["SP2"]["col_base"] = "SP2";
$GLOBALS["roster_active"]["SP2"]["col_name"] = "SP2_ID";
$GLOBALS["roster_active"]["SP2"]["points_col"] = "SP2_Points";
$GLOBALS["roster_active"]["SP2"]["start_col"] = "SP2_Start";
$GLOBALS["roster_active"]["SP2"]["pos"] = "SP";

$GLOBALS["roster_active"]["SP3"]["col_base"] = "SP3";
$GLOBALS["roster_active"]["SP3"]["col_name"] = "SP3_ID";
$GLOBALS["roster_active"]["SP3"]["points_col"] = "SP3_Points";
$GLOBALS["roster_active"]["SP3"]["start_col"] = "SP3_Start";
$GLOBALS["roster_active"]["SP3"]["pos"] = "SP";

$GLOBALS["roster_active"]["RP"]["col_base"] = "RP";
$GLOBALS["roster_active"]["RP"]["col_name"] = "RP_ID";
$GLOBALS["roster_active"]["RP"]["points_col"] = "RP_Points";
$GLOBALS["roster_active"]["RP"]["start_col"] = "RP_Start";
$GLOBALS["roster_active"]["RP"]["pos"] = "RP";

$GLOBALS["roster_bench"]["Bench01"]["col_base"] = "Bench01";
$GLOBALS["roster_bench"]["Bench01"]["col_name"] = "Bench01_ID";
$GLOBALS["roster_bench"]["Bench01"]["points_col"] = "Bench01_Points";
$GLOBALS["roster_bench"]["Bench01"]["pos"] = "B";

$GLOBALS["roster_bench"]["Bench02"]["col_base"] = "Bench02";
$GLOBALS["roster_bench"]["Bench02"]["col_name"] = "Bench02_ID";
$GLOBALS["roster_bench"]["Bench02"]["points_col"] = "Bench02_Points";
$GLOBALS["roster_bench"]["Bench02"]["pos"] = "B";

$GLOBALS["roster"] = array_merge($GLOBALS["roster_active"], $GLOBALS["roster_bench"]);

/*********************************************************************/

$owner_id = 14;

$season = 2019;

// $query = "SELECT * FROM OwnersMain WHERE Owner_ID=$owner_id AND Year=$season";

$query = "SELECT * FROM OwnersMain WHERE Year=$season";

$result = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

while ($row = mysqli_fetch_assoc($result)) {

	echo(json_encode($row));

	$owner_id = $row["Owner_ID"];

	foreach ($GLOBALS["roster"] as $pos => $vals) {
		echo "\nthe pos is: " . $pos;

		if ($pos === "Bench01" || $pos === "Bench02") {
			echo "\nthis is a bench player.";

			if ($pos === "Bench01") {

				if ($row["Bench01_ID"] > 0) {
					$player_id = $row["Bench01_ID"];
					$points = $row["Bench01_Points"];
					$drop_date = $row["Bench01_Drop"];

					$query = "UPDATE owner_roster_current SET bench_date=$drop_date";
					$query .= ", points=$points, benched=1";
					$query .= " WHERE player_id=$player_id AND owner_id=$owner_id";
					$query .= " AND season=$season";

					echo "\n$query";

					mysqli_query($dbconn, $query);

					if (mysqli_error($dbconn)) {
						echo mysqli_error($dbconn);
						exit;
					}
				}
			}

			if ($pos === "Bench02") {

				if ($row["Bench02_ID"] > 0) {
					$player_id = $row["Bench02_ID"];
					$points = $row["Bench02_Points"];
					$drop_date = $row["Bench02_Drop"];

					$query = "UPDATE owner_roster_current SET bench_date=$drop_date";
					$query .= ", points=$points, benched=1";
					$query .= " WHERE player_id=$player_id AND owner_id=$owner_id";
					$query .= " AND season=$season";

					echo "\n$query";

					mysqli_query($dbconn, $query);

					if (mysqli_error($dbconn)) {
						echo mysqli_error($dbconn);
						exit;
					}
				}
			}
		}

		else {

			$col_name = $vals["col_name"];

			$start_col = $vals["start_col"];

			echo "\nthe start col is: " . $start_col;

			if ($row[$start_col] > 0) {
				echo "\nfound an acquired player.";

				$start_date = $row[$start_col];

				$player_id = $row[$col_name];

				echo "\nthe player id is: " . $player_id;

				$points_col = "Points" . $start_date;

				$query = "SELECT points, $points_col FROM Players" . $season . " WHERE Player_ID=$player_id";

				$r = mysqli_query($dbconn, $query);

				if (mysqli_error($dbconn)) {
					echo mysqli_error($dbconn);
					exit;
				}

				$p = mysqli_fetch_assoc($r);

				$points = $p["points"];

				$prev_points = $p[$points_col];

				echo "\nthe points are: " . $points;

				echo "\nthe prev points are: " . $prev_points;

				$id = "2019_" . $owner_id . "_" . $player_id;

				$query = "REPLACE INTO owner_roster_current(id, owner_id, player_id, start_date, prev_points, points, season, acquired)";

				$query .= " VALUES('$id', $owner_id, $player_id, $start_date, $prev_points, $points, $season, 1)";

				echo "\n$query";

				mysqli_query($dbconn, $query);

				if (mysqli_error($dbconn)) {
					echo mysqli_error($dbconn);
					exit;
				}
			}
		}
	}
}
