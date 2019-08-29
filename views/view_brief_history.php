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

/**************************************************************/

if (!($_GET["page_no"])) {
	$page_no = 1;
}
else {
	$page_no = intval($_GET["page_no"]);
}

/**************************************************************/

include INCLUDES_PATH . "/show_page.php";

/**************************************************************/

$content = file_get_contents(HTML_PATH . "/about/brief_history_" . $page_no . ".html");

$title = "A Brief History of the League";

show_page($content, $title);
