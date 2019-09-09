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

include INCLUDES_PATH . '/get_dbconn.php';
include INCLUDES_PATH . '/roster_info.php';

$dbconn = get_dbconn();

/*********************************************************************/

// $owner_table = "owner_rosters_all_time";

$owner_table = "owner_roster_current";

$season = 2019;

/*********************************************************************/

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
		echo "\n********************\n";
		echo "\nthe pos is: " . $pos;
		$drafted = 0;
		$benched = 0;
		$acquired = 0;

		if ($pos === "Bench01" || $pos === "Bench02") {
			echo "\nthis is a benched player.";

			if ($pos === "Bench01") {

				if ($row["Bench01_ID"] > 0) {
					$player_id = $row["Bench01_ID"];
					$points = $row["Bench01_Points"];
					$drop_date = $row["Bench01_Drop"];

					$acquired = 0;
					$benched = 1;
					$drafted = 1;

					$id = get_row_id($season, $owner_id, $player_id);

					echo "\nthe id is: $id";

					$query = "REPLACE INTO " . $owner_table . " SET bench_date=$drop_date";
					$query .= ", points=$points, acquired=$acquired, benched=$benched, drafted=$drafted";
					$query .= ", player_id=$player_id, owner_id=$owner_id";
					$query .= ", season=$season";
					$query .= ", id=" . $id;

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

					$acquired = 0;
					$benched = 1;
					$drafted = 1;

					/**********************************************/
					// Has this player replaced the same slot as Bench01
					// on the roster? In other words, was this player acquired
					// and not drafted?
					$query = "SELECT * FROM Trades WHERE Owner_ID=". $owner_id;
					$query .= " AND Year=$season";

					$trades = mysqli_query($dbconn, $query);

					if (mysqli_error($dbconn)) {
						echo mysqli_error($dbconn);
						exit;
					}

					while($trade = mysqli_fetch_assoc($trades)) {
						if ($trade["AddID"] == $player_id) {
							$acquired = 1;
							$drafted = 0;
						}
					}
					/**********************************************/

					$id = get_row_id($season, $owner_id, $player_id);

					echo "\nthe id is: $id";

					$query = "REPLACE INTO " . $owner_table . " SET bench_date=$drop_date";
					$query .= ", points=$points, acquired=$acquired, benched=$benched, drafted=$drafted";
					$query .= ", player_id=$player_id, owner_id=" . $owner_id;
					$query .= ", season=$season";
					$query .= ", id=" . $id;

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

				$acquired = 1;
				$benched = 0;
				$drafted = 0;

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

				$total_points = $p["points"];

				$prev_points = $p[$points_col];

				$points = $total_points - $prev_points;

				echo "\nthe total points are: " . $total_points;

				echo "\nthe prev points are: " . $prev_points;

				echo "\nthe final points are: " . $points;

				/***********************************************/
				// was this player originally drafted and then dropped?

				$query = "SELECT * FROM Trades WHERE Owner_ID=". $owner_id;
				$query .= " AND Year=$season";
				$query .= " ORDER BY DateShort ASC LIMIT 1";

				$trades = mysqli_query($dbconn, $query);

				if (mysqli_error($dbconn)) {
					echo mysqli_error($dbconn);
					exit;
				}

				$trade = mysqli_fetch_assoc($trades);

				if ($trade["DropID"] == $player_id) {
					$id = get_row_id($season, $owner_id, $player_id, true);
				}
				else {
					$id = get_row_id($season, $owner_id, $player_id, false);
				}

				echo "\nthe id is: $id";

				/***********************************************/

				$query = "REPLACE INTO " . $owner_table . " (id, owner_id, player_id, start_date, prev_points, points, season, acquired, benched, drafted)";

				$query .= " VALUES($id, $owner_id, $player_id, $start_date, $prev_points, $points, $season, $acquired, $benched, $drafted)";

				echo "\n$query";

				mysqli_query($dbconn, $query);

				if (mysqli_error($dbconn)) {
					echo mysqli_error($dbconn);
					exit;
				}
			}
			else {
				echo "\nfound a regular player.";

				$player_id = $row[$col_name];

				echo "\nthe player id is: " . $player_id;

				$query = "SELECT points FROM Players" . $season . " WHERE Player_ID=$player_id";

				$r = mysqli_query($dbconn, $query);

				if (mysqli_error($dbconn)) {
					echo mysqli_error($dbconn);
					exit;
				}

				$p = mysqli_fetch_assoc($r);

				$points = $p["points"];

				echo "\nthe points are: " . $points;

				$id = get_row_id($season, $owner_id, $player_id);

				echo "\nthe id is: $id";

				$query = "REPLACE INTO " . $owner_table . " (id, owner_id, player_id, points, season, drafted)";

				$query .= " VALUES($id, $owner_id, $player_id, $points, $season, 1)";

				echo "\n$query";

				mysqli_query($dbconn, $query);

				if (mysqli_error($dbconn)) {
					echo mysqli_error($dbconn);
					exit;
				}
			}
		}
	}

	// $team_name = mysqli_real_escape_string($dbconn, $row["TeamName"]);

	// $query = "REPLACE INTO owners_all_time SET";
	// $query .= " id='" . $season . "_" . $owner_id . "'";
	// $query .= ", owner_id=" . $owner_id;
	// $query .= ", team_name='" . $team_name . "'";
	// $query .= ", points=" . $row["Points"];
	// $query .= ", salary=" . $row["Salary"];

	// if (empty($row["Bank"])) {
	// 	$query .= ", bank=NULL";
	// }
	// else {
	// 	$query .= ", bank=" . $row["Bank"];
	// }
	// $query .= ", season=" . $season;

	// echo "\n$query";

	// mysqli_query($dbconn, $query);

	// if (mysqli_error($dbconn)) {
	// 	echo mysqli_error($dbconn);
	// 	exit;
	// }
}

function get_row_id($season, $owner_id, $player_id, $include_bit=false) {

	$row_id = "";

	while(strlen($owner_id) < 3) {
		$owner_id = "0" . $owner_id;
	}

	while(strlen($player_id) < 5) {
		$player_id = "0" . $player_id;
	}

	$row_id = $season . $owner_id . $player_id;

	if ($include_bit) {
		$row_id = "1" . $row_id;
	}

	return floatval($row_id);

}

