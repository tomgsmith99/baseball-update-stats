<?php

$dir = "baseball_update_stats";

if (file_exists('/Applications/MAMP/htdocs')) {
	$base_path = '/Applications/MAMP/htdocs';
	$web_home = '/' . $dir;
}
else {
	$base_path = '/var/www/html';
	$web_home = '';
}

// web paths
define("WEB_HOME", $web_home);
define("VIEWS", WEB_HOME . "/views");

// filesystem paths
define("BASE_PATH", $base_path . '/' . $dir);
define("INCLUDES_PATH", BASE_PATH . "/includes");
define("HTML_PATH", BASE_PATH . "/html");

/**************************************************************/

if (array_key_exists("page_no", $_GET)) {
	$page_no = intval($_GET["page_no"]);
}
else {
	$page_no = 1;
}

/**************************************************************/

include INCLUDES_PATH . "/get_page.php";

/**************************************************************/

$content = file_get_contents(HTML_PATH . "/about/brief_history_" . $page_no . ".html");

$title = "A Brief History of the League";

$page = get_page($content, $title);

echo $page;

exit;
