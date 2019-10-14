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

include INCLUDES_PATH . '/ordinalSuffix.php';

$dbconn = get_dbconn();

/*********************************************************************/

$query = "SELECT DISTINCT owner_id FROM owner_rosters_all_time";

echo "\n$query";

$distinct_owners = mysqli_query($dbconn, $query);

if (mysqli_error($dbconn)) {
	echo mysqli_error($dbconn);
	exit;
}

while ($row = mysqli_fetch_assoc($distinct_owners)) {

	$owner_id = $row["owner_id"];

	echo "\nthe owner_id is: " . $owner_id;

	/*******************************************************/
	/* Make sure the owner has a record in the Owners table
	/*******************************************************/

	$query = "SELECT * FROM Owners WHERE Owner_ID = " . $owner_id;

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	if (mysqli_num_rows($result) == 0) {

		echo "\nCould not find a row in the Owners table for owner id " . $owner_id;

		echo "\nInserting row into Owners table...";

		$query = "INSERT INTO Owners SET Owner_ID=". $owner_id;

		$result = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}

	/*******************************************************/
	/* Get the owner's first and last names for debugging purposes
	/*******************************************************/

	$query = "SELECT Fname, Lname, Suffix FROM Members WHERE Member_ID = " . $owner_id;

	$result = mysqli_query($dbconn, $query);

	$inner_row = mysqli_fetch_assoc($result);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	echo "\nUpdating all-time stats for OwnerID $owner_id: " . $inner_row["Fname"] . " " . $inner_row["Lname"] . " " . $inner_row["Suffix"];

	$query = "UPDATE Owners SET 
		Appearances = (SELECT COUNT(Owner_ID) 
			FROM OwnersMain WHERE Owner_ID = $owner_id), 
		RookieYear = (SELECT MIN(Year) 
			FROM OwnersMain WHERE Owner_ID = $owner_id), 
		MostRecentApp = (SELECT MAX(Year) 
			FROM OwnersMain WHERE Owner_ID = $owner_id), 
		BestFinish = (SELECT MIN(Place) 
			FROM OwnersMain WHERE Owner_ID = $owner_id),
		Championships = (SELECT COUNT(Place) 
			FROM OwnersMain WHERE Owner_ID = $owner_id AND Place = 1),
		TopSix = (SELECT COUNT(Place) 
			FROM OwnersMain WHERE Owner_ID = $owner_id AND Place <= 6), 
		FinishSpotTotal = (SELECT SUM(Place) 
			FROM OwnersMain WHERE Owner_ID = $owner_id), 
		SpotTotal = (SELECT SUM(Seasons.NumberOfOwners) 
			FROM Seasons, OwnersMain WHERE Seasons.Season_ID = OwnersMain.Year 
			AND OwnersMain.Owner_ID = $owner_id), 
		AvgFinish = (SELECT ROUND(AVG(Place), 0) 
			FROM OwnersMain WHERE Owner_ID = $owner_id) 
		WHERE Owner_ID = " . $owner_id;

	echo "\n" . $query;

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	/* Find the year(s) that the owner had his best finish */
	/*******************************************************/

	$bestFinishDesc = "";

	$query = "SELECT Year FROM OwnersMain WHERE Owner_ID = $owner_id AND Place = (SELECT MIN(Place) FROM 
		OwnersMain WHERE Owner_ID = $owner_id) ORDER BY Year ASC";

	$inner_result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	while ($inner_row = mysqli_fetch_assoc($inner_result)) {
		$bestFinishDesc .= $inner_row["Year"] . ", ";
	}

	$bestFinishDesc = rtrim($bestFinishDesc);
	$bestFinishDesc = rtrim($bestFinishDesc, ",");

	$query = "UPDATE Owners SET BestFinishDesc='" . $bestFinishDesc . "' WHERE Owner_ID=" . $owner_id;

	echo "\nthe owner's best finish desc is: " . $bestFinishDesc;

	$inner_result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	/*******************************************************/
	// Calculate Overall Rating
	/*******************************************************/

	$OverallRating = 0;

	$query = "SELECT SpotTotal, FinishSpotTotal, Appearances FROM Owners WHERE Owner_ID=" . $owner_id;

	echo "\n" . $query;

	$inner_result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$inner_row = mysqli_fetch_assoc($inner_result);

	echo "\nAppearances is: " . $inner_row['Appearances'];

	echo "\nFinishSpotTotal is: " . $inner_row['FinishSpotTotal'];

	echo "\nSpotTotal is: " . $inner_row['SpotTotal'];

	if ($inner_row['Appearances'] > 3) {
		$OverallRating = 1000 - (intval($inner_row['FinishSpotTotal'] / $inner_row['SpotTotal'] * 1000));
	}

	$query = "UPDATE Owners SET OverallRating = $OverallRating WHERE Owner_ID = $owner_id";

	mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}
}

/*******************************************************/
// Create numeric ranks and descriptions
/*******************************************************/

$query = "SELECT * FROM Owners ORDER BY Appearances DESC";

updateRankDescriptions($query, "Appearances", "AppDesc");

/*******************************************************/

$query = "SELECT * FROM Owners WHERE Championships > 0 ORDER BY Championships DESC";

updateRankDescriptions($query, "Championships", "ChampDesc");

/*******************************************************/

$query = "SELECT * FROM Owners WHERE TopSix > 0 ORDER BY TopSix DESC";

updateRankDescriptions($query, "TopSix", "TopSixDesc");

/*******************************************************/

$query = "SELECT * FROM Owners ORDER BY AvgFinish ASC";

updateRankDescriptions($query, "AvgFinish", "AvgFinishDesc");

/*******************************************************/

$query = "SELECT * FROM Owners ORDER BY OverallRating DESC";

updateRankDescriptions($query, "OverallRating", "RatingDesc");

/*******************************************************/

function updateRankDescriptions($query, $colName, $descColName) {

	global $dbconn;

	$result = mysqli_query($dbconn, $query);

	if (mysqli_error($dbconn)) {
		echo mysqli_error($dbconn);
		exit;
	}

	$rank = 0;
	$previousVal = 0;
	$count = 0;

	while ($row = mysqli_fetch_assoc($result)) {

		if ($row[$colName] == $previousVal) { $count++; }
		else {

			$count++;

			$rank = $count;

			$previousVal = $row[$colName];
		}

		$query = "SELECT $colName, Owner_ID FROM Owners WHERE $colName=" . $row[$colName];

		echo "\n$query";

		$innerResult = mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}

		if (mysqli_num_rows($innerResult) < 2) {
			$rankDesc = ordinal_suffix($rank);
		}
		else { 

			$rankDesc = "Tied for " . ordinal_suffix($rank) . " with " . (mysqli_num_rows($innerResult) - 1) . " other owner";

			if (mysqli_num_rows($innerResult) > 2) { $rankDesc .= "s"; }

			$rankDesc .= ".";
		}

		$query = "UPDATE Owners SET $descColName = '$rankDesc' WHERE Owner_ID=" . $row['Owner_ID'];

		mysqli_query($dbconn, $query);

		if (mysqli_error($dbconn)) {
			echo mysqli_error($dbconn);
			exit;
		}
	}
}
