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

$oldest_season = 1993;

$query = "SELECT MAX(season) AS latest_season FROM owner_rosters_all_time";

$result = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

$row = mysqli_fetch_assoc($result);

$latest_season = $row["latest_season"];

/**************************************************************/

$j = 0;

$rows = "";

for ($i = $oldest_season; $i <= $latest_season; $i++) {
	if ($j === 0) { $rows .= "<tr>"; }

	$rows .= "<td><a href = '{{views}}/view_final_standings.php?season=" . $i . "'>" . $i . "</a></td>";

	if ($j === 4) {
		$rows .= "</tr>\n";
		$j = 0;
	}
	else { $j++; }
}

$content = file_get_contents(HTML_PATH . "/choose_season.html");

$content = str_replace("{{ROWS}}", $rows, $content);

/**************************************************************/

$title = "choose a season";

show_page($content, $title);
