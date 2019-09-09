<?php

include 'ordinalSuffix.php';

class Team {

	public function __construct($row) {
		$this->owner_fname = $row["Fname"];
		$this->owner_lname = $row["Lname"];
		$this->owner_suffix = $row["Suffix"];
		$this->owner_headshot = $row["HeadShot"];
		$this->owner_id = $row["Owner_ID"];
		$this->team_name = $row["TeamName"];
		$this->points = $row["Points"];
		$this->salary = $row["Salary"];
		$this->bank = $row["Bank"];
		$this->reserve = $row["Reserve"];
		$this->place = $row["Place"];
		$this->recent = $row["Recent"];
		$this->yesterday = $row["Yesterday"];
		$this->season = $row["Year"];

		foreach ($GLOBALS["roster"] as $pos => $vals) {
			$points_col = $vals["col_base"] . "_Points";
			$this->$pos["points"] = $row[$points_col];
		}

		foreach ($GLOBALS["roster_active"] as $pos => $vals) {
			$id_col = $vals["col_base"] . "_ID";
			$this->$pos["id"] = $row[$id_col];

			$start_col = $vals["col_base"] . "_Start";
			$this->$pos["start"] = $row[$start_col];
		}

		foreach ($GLOBALS["roster_bench"] as $pos => $vals) {
			$id_col = $vals["col_base"] . "_ID";
			$this->$pos["id"] = $row[$id_col];

			$drop_col = $vals["col_base"] . "_Drop";
			$this->$pos["drop"] = $row[$drop_col];
		}
	}

	public function get_html_table($anchor) {

		if ($this->season < 2004) {
			$table = file_get_contents(HTML_PATH . "/team_b4_2004.html");
		}
		else {
			$table = $GLOBALS["team_html_template"];
			$active_players = "";

			foreach ($GLOBALS["roster_active"] as $pos => $vals) {

				$player_data = $this->get_player_data($this->$pos["id"]);

				$this_player = new Player($player_data, $this->season);

				$this_player->set_display_points($this->$pos["points"]);
				$this_player->set_display_pos($pos);
				$this_player->set_start_date($this->$pos["start"]);

				$active_players .= $this_player->get_html_row() ."\n";
			}

			$bench = "";

			foreach ($GLOBALS["roster_bench"] as $pos => $vals) {

				if ($this->$pos["id"] > 0) {

					$player_data = $this->get_player_data($this->$pos["id"]);

					$this_player = new Player($player_data, $this->season);

					$this_player->set_display_points($this->$pos["points"]);
					$this_player->set_display_pos($pos);

					$bench .= $this_player->get_html_row() ."\n";
				}
			}

			if ($bench != "") {
				$bench = "<tr style='text-align: center'><td colspan = '6'>Benched players</td></tr>\n" . $bench;
			}
			$table = str_replace("{{active_players}}", $active_players, $table);
			$table = str_replace("{{bench}}", $bench, $table);
		}


		$table = str_replace("{{ANCHOR}}", $anchor, $table);
		$table = str_replace("{{owner_name}}", $this->get_owner_name("FNF"), $table);
		$table = str_replace("{{team_name}}", $this->get_team_name(), $table);

		$table = str_replace("{{place}}", ordinal_suffix($this->place), $table);
		$table = str_replace("{{season}}", $this->season, $table);

		$table = str_replace("{{total_salary}}", $this->salary, $table);
		$table = str_replace("{{points}}", $this->points, $table);

		return $table;
	}

	private function get_player_data($player_id) {

		$dbconn = $GLOBALS["dbconn"];

		$player_table = "Players" . $this->season;

		if (array_key_exists("players", $GLOBALS)) {
			$player_data = $GLOBALS["players"][$player_id];
		}
		else {
			$query = "SELECT t2.Pos, t1.Fname, t1.Lname, t2.Team, t2.Salary, t2.Value FROM Players as t1, $player_table as t2 WHERE t1.Player_ID = " . $player_id . " AND t1.Player_ID = t2.Player_ID";

			$result = mysqli_query($dbconn, $query);

			$player_data = mysqli_fetch_assoc($result);
		}

		return $player_data;

	}

	private function get_team_name() {
		if ($this->team_name) {
			return " - " . $this->team_name;
		}
		else {
			return "";
		}
	}

	public function get_table_row() {

		$season = $this->season;

		$table_row = $GLOBALS["html_templates"]["team_row"];

		$vals = array("place", "team_name", "points");

		foreach ($vals as $val) {
			$table_row = str_replace("{{" . $val . "}}", $this->$val, $table_row);
		}

		$table_row = str_replace("{{owner_headshot}}", $this->get_owner_headshot(), $table_row);

		if ($season < 2003) {
			$table_row = str_replace("{{owner_name}}", $this->get_owner_name("FNF"), $table_row);
		}
		else {
			$table_row = str_replace("{{owner_name}}", $this->get_owner_name_with_link("", TRUE, "FNF"), $table_row);
		}

		return $table_row;
	}

	public function setTeam($season) {
		// global $config;

		$dbconn = $GLOBALS["dbconn"];

		// $position_names = $config["positionNames"];

		$query = "SELECT * FROM OwnersMain WHERE Owner_ID = " . $this->id;
		$query .= " AND Year = $season";

		$result = mysqli_query($dbconn, $query);

		$row = mysqli_fetch_array($result);

		$this->teamName = $row["TeamName"];
		$this->points = $row["Points"];
		$this->salary = $row["Salary"];
		$this->bank = $row["Bank"];
		$this->reserve = $row["Reserve"];
		$this->place = $row["Place"];
		$this->recent = $row["Recent"];
		$this->yesterday = $row["Yesterday"];

		foreach ($GLOBALS["roster"] as $pos => $vals) {
			$points_col = $vals["col_base"] . "_Points";
			$this->$pos["points"] = $row[$points_col];
		}

		foreach ($GLOBALS["roster_active"] as $pos => $vals) {
			$id_col = $vals["col_base"] . "_ID";
			$this->$pos["id"] = $row[$id_col];

			$start_col = $vals["col_base"] . "_Start";
			$this->$pos["start"] = $row[$start_col];
		}

		foreach ($GLOBALS["roster_bench"] as $pos => $vals) {
			$id_col = $vals["col_base"] . "ID";
			$this->$pos["id"] = $row[$id_col];

			$drop_col = $vals["col_base"] . "Drop";
			$this->$pos["drop"] = $row[$drop_col];
		}

		// for ($i = 0; $i < count($position_names); $i++) {
		//     $id_colname = $position_names[$i] . "_ID";
		//     $start_colname = $position_names[$i] . "_Start";
		//     $points_colname = $position_names[$i] . "_Points";

		//     $this->_Roster[$i]["ID"] = $row[$id_colname];
		//     $this->_Roster[$i]["Start"] = $row[$start_colname];
		//     $this->_Roster[$i]["Points"] = $row[$points_colname];
		// }
		
		// $this->RosterTemplate = new RosterTemplate();

//        foreach ($this->RosterTemplate->getPosList() as $pos) {
		// foreach ($GLOBALS["activeRosterSpots"] as $pos) {

		//     // $position = new Position($pos);
		//     $this->activePlayers[$pos]["id"] = $row[$pos . "_ID"];
		//     $this->activePlayers[$pos]["start"] = $row[$pos . "_Start"];
		//     $this->activePlayers[$pos]["points"] = $row[$pos . "_Points"];
		// }
		
		// foreach ($this->RosterTemplate->getBenchList() as $benchPos) {
			
		//     $position = new Position($benchPos);

		//     $this->bench[$benchPos]["id"] = $row[$position->getIDcolName()];
		//     $this->bench[$benchPos]["drop"] = $row[$position->getDropcolName()];
		//     $this->bench[$benchPos]["points"] = $row[$position->getPointsColName()];

		// }

		// $this->_Bench01ID = $row["Bench01ID"];
		// $this->_Bench01Pts = $row["Bench01_Points"];
		// $this->Bench01Drop = $row["Bench01Drop"];

		// $this->_Bench02ID = $row["Bench02ID"];
		// $this->_Bench02Pts = $row["Bench02_Points"];
		// $this->Bench02Drop = $row["Bench02Drop"];


	}

	function GetHTMLrowWithRank($rank) {

		$row = "<tr>";
		$row .= "<td align = 'right'>$rank</td>";
		$row .= "<td>" . $this->getNameWithLink() . "</td>";
		$row .= "<td>" . $this->teamName . "</td>";
		$row .= "<td align = 'right'>" . $this->getPoints() . "</td>";
		$row .= "<td align = 'right'>" . $this->getPoints("yesterday") . "</td>";
		$row .= "<td align = 'right'>" . $this->getPoints("recent") . "</td>";

		$row .= "</tr>\n";

		return $row;
	}

	function getPoints($cat = "points") {
		$pts = $this->$cat;

		if ($pts < 0) { return "n/a"; }
		else { return $pts; }
	}

	function get_owner_headshot() {
		if ($this->owner_headshot === "") { return ""; }
		else { return "<img src='" . $this->owner_headshot . "' />"; }
	}

	function getHeadShot() {
		if ($this->headshot === NULL) { return ""; }
		else { return $this->headshot; }
	}

	function get_link_for_dropdown() {
		$val = "<a class='dropdown-item' href='#";
		$val .= $this->id;
		$val .= "'>";
		$val .= $this->getName("LNF");
		$val .= "</a>";
	}

	function getPlayerID($rosterPos, $year) { return $this->_Roster[$rosterPos]["ID"]; }
	
	function wasPlayerAcquired($rosterPos, $year) { 
						
		return $this->_Roster[$rosterPos]["Start"];
	}

	function getPlayerStartDate($rosterPos) { 
						
		return $this->_Roster[$rosterPos]["Start"];
	}

	function getSalaryTotal() {
		
		$totalSalary = 0;
		
		foreach ($this->RosterTemplate->getPosList() as $pos) {
			$playerID = $this->activePlayers[$pos]["id"];
			
			if ($this->activePlayers[$pos]["start"] > 0) {
				
				$playerID = $this->getOrigPlayer($this->activePlayers[$pos]["id"]);
			}
			
			$thisPlayer = new Player($playerID);

			$totalSalary += $thisPlayer->getSalary();
			
		}
		
		return $totalSalary;
	}

	function getOrigPlayer($newPlayerID) {

		global $this_year_trades_table;
		
		$query = "SELECT * FROM $this_year_trades_table WHERE Owner_ID = " . $this->_id . " AND AddID = " . $newPlayerID;

		$result = mysql_query($query);
		
		if (mysql_num_rows($result) > 1) { echo "<p>error looking up trade.</p>"; }
		else {
			$row = mysql_fetch_array($result);
			return $row["DropID"];
		}
		
	}

	function playerIsAlreadyOnRoster($playerID, $playerPosition) { 
			// Pitchers and Outfielders only

			$x = 0;

			if ($playerPosition == "OF") {

					if ($this->_Roster[5]["ID"] == $playerID) { $x = 1; }
					if ($this->_Roster[6]["ID"] == $playerID) { $x = 1; }
					if ($this->_Roster[7]["ID"] == $playerID) { $x = 1; }

					if ($this->_Bench01ID == $playerID) { $x = 1; }
					if ($this->_Bench02ID == $playerID) { $x = 1; }

					return $x;

			}

			if ($playerPosition == "SP") {

					if ($this->_Roster[8]["ID"] == $playerID) { $x = 1; }
					if ($this->_Roster[9]["ID"] == $playerID) { $x = 1; }
					if ($this->_Roster[10]["ID"] == $playerID) { $x = 1; }

					if ($this->_Bench01ID == $playerID) { $x = 1; }
					if ($this->_Bench02ID == $playerID) { $x = 1; }

					return $x;

			}

			return $x;
	}

	function Update() {
		global $config;
		$yesterday = $config["yesterday"];
		$recent = $config["recentDay"];

		$content = "";

		$position_names = $config["positionNames"];

		$this->points = 0;
		$this->recent = 0;
		$this->yesterday = 0;

		// This query gets continued below
		$query = "UPDATE OwnersMain SET ";

		for ($i = 0; $i < count($position_names); $i++) {

			$this_player = new Player($this->_Roster[$i]["ID"]);

			$col_name = $position_names[$i] . "_Points";

			if ($this->_Roster[$i]["Start"] > 0) {
				$excluded_points = $this_player->getPointsForDay($this->_Roster[$i]["Start"]);

				$content .= "<p><font color ='black'>Found an acquired player. Excluded points: " . $excluded_points . "</font></p>";

				$points = $this_player->points - $excluded_points;
			}
			else {
				$points = $this_player->points;

				if ($points) {}
				else { $points = 0; }

			}

			$query .= " $col_name = $points, ";

			$this->points += $points;

			$this->yesterday += $this_player->yesterday;
			$this->recent += $this_player->recent;

			// $this->yesterday += $this_player->getPointsForDay($yesterday);
			// $this->recent += $this_player->getPointsForDay($recent);

			// if ($this_player->yesterday > 0) { $this->yesterday += $this_player->yesterday; }
			// if ($this_player->recent > 0) { $this->recent += $this_player->recent; }

		}

		// $this->yesterday = $this->points - $this->yesterday;
		// $this->recent = $this->points - $this->recent;

		if ($this->_Bench01ID > 0) {

			if ($this->_Bench01Pts > 0) { $this->points += $this->_Bench01Pts; }
			else {
				$this_player = new Player($this->_Bench01ID);
				$this->points += $this_player->getPointsForDay($this->Bench01Drop);

				$query .= "Bench01_Points = " . $this_player->getPointsForDay($this->Bench01Drop) . ", ";
			}
		}

		if ($this->_Bench02ID > 0) {

			if ($this->_Bench02Pts > 0) { $this->points += $this->_Bench02Pts; }
			else {
				$this_player = new Player($this->_Bench02ID);
				$this->points += $this_player->getPointsForDay($this->Bench02Drop);

				$query .= "Bench02_Points = " . $this_player->getPointsForDay($this->Bench02Drop) . ", ";
			}
		}

		$query .= "Points = $this->points";
		$query .= ", Recent = $this->recent";
		$query .= ", Yesterday = $this->yesterday";

		// $query .= ", Salary = $this->_total_Salary";

		// $bank = 13000 - $this->_total_Salary;

		// $query .= ", Bank = $bank";

		$query .= " WHERE Owner_ID = $this->id AND Year = " . $config["thisYear"];
		// echo "<p>query: $query";

		mysqli_query($config["dbconn"], $query);

		$content .= "<p>" . mysqli_error($config["dbconn"]);

		return $content;
	}

	public function displayTeam($mode) {
		global $config;

		$position_names = $config["positionNames"];

		$num_cols = 10;
		$team_string = "\n<a name = '" . $this->id . "'></a>\n";
 
		$team_string .= "<table border = '1' class = 'text'>\n";

		$namewidth = $num_cols;

		$team_string .= "<tr><td colspan = '$namewidth'>" . $this->displayNameFirstNameFirst() . "</td>";

		$team_string .= "</tr>\n";

		$team_string .= "<tr><td colspan = '$num_cols'>" . $this->teamName . "</td></tr>\n";
 
		$team_string .= "<tr><td><b>POS</b></td><td><b>Name</b></td><td><b>Team</b></td><td><b>Salary</b></td><td><b>Pts</b></td><td><b>Y'day</b></td><td><b>Recent</b></td><td><b>Val</b></td>";

		if ($mode == 'edit') { $team_string .= "<td><b>ESPN ID</b></td><td><b>Drop</b></td>"; }

		$team_string .= "</tr>\n";
								
		for ($i = 0; $i < count($position_names); $i++) {
									
			$this_player = new Player($this->_Roster[$i]["ID"]);

			$team_string .= $this_player->GetHTMLrow($this->_Roster[$i]["Points"], $mode, $this->id);

		}

		if ($this->Bench01["id"] != 0) {
				$team_string .= "<tr><td colspan = '$num_cols' align = 'center'><b>Benched players</b></td></tr>\n";

				$this_player = new Player($this->Bench01["id"]);

				$team_string .= $this_player->GetHTMLrow($this->Bench01["points"], "display", $this->id);
		}
		if ($this->Bench02["id"] != 0) { 
				$this_player = new Player($this->Bench02["id"]);
				$team_string .= $this_player->GetHTMLrow($this->Bench02["points"], "display", $this->id);
		}

		$team_string .= "<tr><td colspan = '3'>";

		$team_string .= "<table border = '0' width = '100%'><tr><td><b>" . ordinal_suffix($this->place, 0) . " place</b></td><td align = 'right'><b>Bank: $" . $this->bank . "</b></td></tr></table></td>";

		$team_string .= "<td align = 'right'><b>$" . $this->salary . "</b></td><td align = 'right'><b>$this->points</b></td>";

		$team_string .= "<td align = 'right'><b>" . $this->displayYesterdayPoints() . "</b></td>";

		$team_string .= "<td align = 'right'><b>" . $this->displayRecentPoints() . "</b></td><td>&nbsp;</td></tr>\n";

		$team_string .= "</table>\n";

		return $team_string;
	}

	// Returns either an int > 0 or a string: "n/a"
	function displayRecentPoints() {
			if (($this->recent == "") || ($this->recent < 0)) { return "n/a"; }
			else { return $this->recent; }
	}

	// Returns either an int > 0 or a string: "n/a"
	function displayYesterdayPoints() {
			if (($this->yesterday == "") || ($this->yesterday < 0)) { return "n/a"; }
			else { return $this->yesterday; }
	}

	function getFname() { return $this->_Fname; }

	function get_owner_name($style = "FNF") {
		if ($style == "FNF") {
			$name = $this->owner_fname . " " . $this->owner_lname . $this->get_suffix();
		}
		else if ($style == "nickname") {
			$name = $this->owner_nickname;
		}
		else if ($style == "LNF") {
			$name = $this->owner_lname . ", " . $this->owner_fname . $this->get_suffix();
		}
		return $name;
	}

	function get_owner_name_with_link($link_class = "", $same_page = FALSE, $style = "FNF") {

		$link = '<a {{CLASS}} href="{{HREF}}">{{NAME}}</a>';

		if ($link_class === "") {
			$link = str_replace("{{CLASS}} ", "", $link);
		}
		else {
			$link = str_replace("{{CLASS}}", "class='$link_class'", $link);
		}

		$link = str_replace("{{LINK_CLASS}}", $link_class, $link);

		if ($same_page) {
			$href = "#" . $this->owner_id;
		}
		else {
			$href = $this->getURL();
		}

		$link = str_replace("{{HREF}}", $href, $link);

		$link = str_replace("{{NAME}}", $this->get_owner_name($style), $link);

		return $link;

	}

	function getURL() {
		global $config;

		return $config["views"] . "/viewSingleOwner.php?ownerID=" . $this->id;
	}
	
	function getTeamName() {
		if ($this->teamName === NULL) { return ""; }
		else { return $this->teamName; }
	}

	function get_suffix() {
		if (!empty($this->owner_suffix)) { return " " . $this->owner_suffix; }
		else { return ""; }
	}

	function displayNameLastNameFirst() { 
			return ($this->lname . ", " . $this->fname . $this->getSuffix());
	}

	function displayNameFirstNameFirst() {
			return ($this->fname . " " . $this->lname . $this->getSuffix());
	}

	function displayNameFirstNameFirstWithLink() {
			global $baseballHome;

			return ("<a class = 'player' href = '" . $baseballHome . "admin/view_single_owner.php?owner_id=" . $this->_id . "'>" . $this->_Fname . " " . $this->_Lname . $this->getSuffix() . "</a>");
	}

	function displayShortNameWithLink() {
			global $baseballHome;

			return ("<a class = 'player' href = '" . $baseballHome . "admin/view_single_owner.php?owner_id=" . $this->_id . "'>" . $this->_Fname . $this->getSuffix() . "</a>");
	}

	function displayNicknameWithLink() {
		global $config;

		return ("<a class = 'player' href = '" . $baseballHome . "admin/view_single_owner.php?owner_id=" . $this->id . "'>" . $this->nickname . "</a>");
	}
	
}