<?php

/***********************************************************/
/* owner drop-down list
/***********************************************************/

function get_owner_drop_down_list($season) {
	global $dbconn;

	$owners = get_all_owners_for_year($season, "Lname, Fname ASC");

	$owner_drop_down = "";

	$owner_drop_down_row = file_get_contents(HTML_PATH . "/owner_drop_down_row.html");

	foreach ($owners as $owner => $data) {

		$this_row = str_replace("{{owner_id}}", $data["owner_id"], $owner_drop_down_row);

		$this_row = str_replace("{{LNF}}", $data["LNF"], $this_row);

		$owner_drop_down .= $this_row . "\n";
	}

	return $owner_drop_down;
}
