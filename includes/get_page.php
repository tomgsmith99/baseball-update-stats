<?php

function get_page($content, $title) {

	$seasons = get_seasons();

	$home = get_home();

	$page = file_get_contents(HTML_PATH . "/page.html");

	$page = str_replace("{{seasons}}", $seasons, $page);

	$page = str_replace("{{TITLE}}", "Diffendorf baseball: " . $title, $page);

	$page = str_replace("{{CONTENT}}", $content, $page);

	$page = str_replace("{{home}}", $home, $page);

	$page = str_replace("{{web_home}}", WEB_HOME, $page);

	$page = str_replace("{{views}}", VIEWS, $page);

	return $page;
}

function get_home() {
	if (WEB_HOME == "") {
		return "/";
	}
	else {
		return WEB_HOME;
	}
}

function get_seasons() {
	global $dbconn;

	$oldest_season = 1993;

	$seasons = "";

	$query = "SELECT * FROM Seasons WHERE status='closed'";
	$query .= " ORDER BY Season_ID DESC LIMIT 5";

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	while ($row = mysqli_fetch_assoc($result)) {

		$seasons .= "\n<a class='dropdown-item' href='{{views}}/view_final_standings.php?season=";
		$seasons .= $row["Season_ID"] . "'>" . $row["Season_ID"] . "</a>";

		$next_season = $row["Season_ID"] - 1;
	}


	$seasons .= "\n<a class='dropdown-item' href='{{views}}/choose_season.php";
	$seasons .= "'>" . $oldest_season . " - " . $next_season . "</a>";

	return $seasons;
}
