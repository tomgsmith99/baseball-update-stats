<?php

include INCLUDES_PATH . '/roster_order.php';

function get_all_teams_html($owners, $players, $season) {
	global $dbconn;
	global $roster_order;

	$all_teams_html = "";

	$owner_cols = ["bank", "FNF", "owner_id", "team_name", "points", "season", "salary"];

	$player_cols = ["pos", "FNF", "team", "salary", "value", "picked", "points"];

	if ($season === $GLOBALS["this_year"]) {
		$owner_table = "owner_roster_current";
		$player_table = "players_current";
		$team_template = file_get_contents(HTML_PATH . "/team.html");
		$player_row_template = file_get_contents(HTML_PATH . "/player_row_short.html");

		$owner_cols = array_merge($owner_cols, ["recent", "yesterday"]);

		$player_cols = array_merge($player_cols, ["recent", "yesterday"]);

		$get_team_sql = file_get_contents(BASE_PATH . "/queries/get_team.sql");
	}
	else {
		$owner_table = "owner_rosters_all_time";
		$player_table = "players_all_time";
		$team_template = file_get_contents(HTML_PATH . "/team_past.html");
		$player_row_template = file_get_contents(HTML_PATH . "/player_row_short_past.html");

		$get_team_sql = file_get_contents(BASE_PATH . "/queries/get_team_past.sql");
	}

	$i = 1;

	foreach ($owners as $owner => $row) {

		$place = ordinal_suffix($i);

		$this_team = str_replace("{{place}}", $place, $team_template);

		foreach ($owner_cols as $col) {
			if ($col == "team_name") {
				if ($row[$col]) {
					$row[$col] = " - " . $row[$col];
				}
			}
			$this_team = str_replace("{{" . $col . "}}", $row[$col], $this_team);
		}

		$query = $get_team_sql;
		$query = str_replace("{{owner_table}}", $owner_table, $query);
		$query = str_replace("{{player_table}}", $player_table, $query);
		$query = str_replace("{{season}}", $season, $query);
		$query = str_replace("{{owner_id}}", $owner, $query);

		$result = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		$bench_display_style = "display: none";

		$benched_players = "";

		$of = 0;
		$sp = 0;

		while ($player = mysqli_fetch_assoc($result)) {

			$player_row = $player_row_template;

			foreach ($player_cols as $col) {
				$player_row = str_replace("{{" . $col . "}}", $player[$col], $player_row);
			}

			if ($player["benched"] == 1) {
				$bench_display_style = "";
				$benched_players .= "\n" . $player_row;
			}
			else {

				$pos = $player["pos"];

				if ($pos === "OF") {
					$of++;
					$pos = $pos . $of;
				}

				if ($pos === "SP") {
					$sp++;
					$pos = $pos . $sp;
				}

				$roster_pos = $roster_order[$pos];

				$font_style = "normal";

				if ($player["acquired"]) {
					$font_style = "italic";
				}

				$player_row = str_replace("{{font-style}}", $font_style, $player_row);

				$active_players_arr[$roster_pos] = $player_row;
			}
		}

		$active_players = "";

		for ($j=0; $j < sizeof($active_players_arr); $j++) {
			$active_players .= "\n" . $active_players_arr[$j];
		}

		$this_team = str_replace("{{active_players}}", $active_players, $this_team);
		$this_team = str_replace("{{benched_players}}", $benched_players, $this_team);
		$this_team = str_replace("{{show_bench}}", $bench_display_style, $this_team);

		$all_teams_html .= $this_team . "\n";

		$i++;
	}

	return $all_teams_html;
}
