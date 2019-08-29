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
define("HTML_PATH", BASE_PATH . "/html");
define("VIEWS", WEB_HOME . "/views");

/**************************************************************/

include INCLUDES_PATH . "/show_page.php";
include INCLUDES_PATH . '/get_dbconn.php';

/**************************************************************/

$dbconn = get_dbconn();

/**************************************************************/

$query = "SELECT t1.Owner_ID, t1.Points, t1.Place, t1.Year, t2.FNF";
$query .= " FROM OwnersMain as t1, Members as t2";
$query .= " WHERE t1.Owner_ID = t2.Member_ID AND Place < 7";
$query .= " ORDER BY Year DESC, Place, t2.Lname, t2.Fname ASC";

$result = mysqli_query($dbconn, $query);

$season = 0;

$grid = "";

while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

	if ($row["Year"] < $season || $season == 0) {
		$season = $row["Year"];
		if ($grid == "") { $grid = "\n<tr>"; }
		else { $grid .= "</tr>\n<tr>"; }

		$grid .= "<td><a href = '" . VIEWS . "/viewFinalStandings.php?season=" . $row["Year"] . "'>";
		$grid .= $row["Year"] . "</td>";
	}

	$grid .= "<td><a href = '" . VIEWS . "/owner.php?owner_id=" . $row["Owner_ID"] . "'>";
	$grid .= $row["FNF"] . "</a></td>";
}

$grid .= "</tr>";

/**************************************************************/

$content = file_get_contents(HTML_PATH . "/finishes.html");

$content = str_replace("{{ROWS}}", $grid, $content);

/**************************************************************/

$title = "all-time finishes";

show_page($content, $title);
