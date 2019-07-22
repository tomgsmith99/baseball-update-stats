<?php

date_default_timezone_set("America/New_York");

if (file_exists("/Applications/MAMP/htdocs/baseball_update_stats")) {

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

}

include "get_dbconn.php";
include "initialize_table.php";

$GLOBALS["dbconn"] = get_dbconn();
$GLOBALS["today"] = date("z");

/*************************************************************/

initialize_table(0);
