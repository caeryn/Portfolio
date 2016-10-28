<?
/******************************************************************************************************************************
BRIDGES > IACL

Version:	1.0

Script:  	housekeeping.php

Purpose:	Handle various automated functions

Requires:	none

Optional:	varies

Created: 	2011-09-03, Caeryn Price

Updated: 	2011-10-02, Caeryn Grant

******************************************************************************************************************************/

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Format the Date without the time
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function DateWithoutTime ($date_old) {

	$date_new = $date_old;
	if ($date_old != "") {
		$explodey_all = explode_date($date_old);
		$explodey_day = explode(" ", $explodey_all["day"]);
		$date_new = $explodey_all["year"] . "-" . $explodey_all["month"] . "-" . $explodey_day[0];
	}
	return $date_new;
	
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Date Range Overlap Check
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function checkDateOverlap ($a_start = null, $a_end = null, $b_start = null, $b_end = null) {
	
	// presumes dates have been checked to ensure that start is before end for both ranges...
	
	$overlap = 0;
	$msg = "";
	
	// make sure the dates are in the right format for the comparison
	$a_start = DateWithoutTime($a_start);
	$a_end  = DateWithoutTime($a_end);
	$b_start = DateWithoutTime($b_start);
	$b_end= DateWithoutTime($b_end);
	
	// set b start and end dates
	if ($b_start != null && $b_end != null) {

		if ($a_end != null && $a_end < $b_start) {
			$overlap = 0;
			$msg .= "dbr, definite a end (".$a_end.") less than b start (".$b_start.")";
		}
		else if ($a_start != null && $a_start > $b_end) {
			$overlap = 0;
			$msg .= "dbr, definite a start (".$a_start.") greater than b end (".$b_end.")";
		}
		else {
			$overlap = 1;
			$msg .= "dbr, indefinite a range (".$a_start." to ".$a_end.") overlaps b range (".$b_start." to ".$b_end.")";
		}

	}

	// set b end date
	else if ($b_start == null && $b_end != null) {
		
		if ($a_start != null && $a_start > $b_end) {
			$overlap = 0;
			$msg .= "ibs, definite a start (".$a_start.") greater than b end (".$b_end.")";
		}
		else {
			$overlap = 1;
			$msg .= "ibs, a range (".$a_start." to ".$a_end.") overlaps b range (".$b_start." to ".$b_end.")";
		}

	}
	
	// set b start date
	else if ($b_start != null && $b_end == null) {
		
		if ($a_end != null && $a_end < $b_start) {
			$overlap = 0;
			$msg .= "ibe, definite a end (".$a_end.") less than b start (".$b_start.")";
		}
		else {
			$overlap = 1;
			$msg .= "ibe, a range (".$a_start." to ".$a_end.") overlaps b range (".$b_start." to ".$b_end.")";
		}
		
	}
	
	// no b set dates
	else {
		$overlap = 1;
		$msg .= "ibr, a range (".$a_start." to ".$a_end.") overlaps b range (".$b_start." to ".$b_end.")";
		}
	
	return $overlap;

}
 	
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Create a select list
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function build_selectoption($inputname, $table, $display = "name", $selected = "", $limit ="") {
	
	$return = "";
	
	$return .= "<select name=\"" . $inputname . "\">";
	$return .= "<option value=\"null\">Please select...</option>";
	
//	Get the list values
	$sql = "SELECT id, " . $display . " FROM " . $table;
	if ($limit != "") {
	//	WHERE clause
		$list = explode("--", $limit);
		$where = "";
		foreach ($list as $count => $field) {
			if ($where != "") $where .= " AND";
			if (strstr($field, "_LK_") > 0) {
				$pair = explode("_LK_", $field);
				$where .= " " . $pair[0] . " LIKE \"%" . $pair[1] . "%\"";
			} elseif (strstr($field, "_EQ_")) {
				$where .= str_replace("_EQ_", " = ", $field);
			} elseif (strstr($field, "_IN_") > 0) {
				$pair = explode("_IN_", $field);
				$where .= " " . $pair[0] . " IN (" . $pair[1] . ")";
			} 
		}
		$sql .= " WHERE " . $where;
	}	
	connectDatabase();
	$result = db_query($sql);
	
//	Build the list
	while ($row = db_fetch_assoc($result)) {
		$return .= "<option value=\"" . $row["id"] . "\"";
		if ($selected == $row["id"]) $return .= " selected";
		$return .= ">";
		$list = explode("--", $display);
		$temp = "";
		foreach ($list as $count => $field) {
			if ($temp != "") $temp .= ", ";
			$temp .= $row[$field];
		}
		$return .= $temp . "</option>";
	}
	db_close();
	
	return $return;	
	
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Create a radio list
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function build_radiooption($inputname, $table, $display = "name", $selected = "", $limit ="") {
	
	$return = "<ul>";
	
//	Get the list values
	$sql = "SELECT id, " . $display . " FROM " . $table;
	if ($limit != "") {
	//	WHERE clause
		$list = explode("--", $limit);
		$where = "";
		foreach ($list as $count => $field) {
			if ($where != "") $where .= " AND";
			if (strstr($field, "_LK_") > 0) {
				$pair = explode("_LK_", $field);
				$where .= " " . $pair[0] . " LIKE \"%" . $pair[1] . "%\"";
			} elseif (strstr($field, "_EQ_")) {
				$where .= str_replace("_EQ_", " = ", $field);
			} elseif (strstr($field, "_IN_") > 0) {
				$pair = explode("_IN_", $field);
				$where .= " " . $pair[0] . " IN (" . $pair[1] . ")";
			} 
		}
		$sql .= " WHERE " . $where;
	}	
	connectDatabase();
	$result = db_query($sql);
	
//	Build the list
	while ($row = db_fetch_assoc($result)) {
		$return .= "<div><radio name=\"$inputname\" value=\"" . $row["id"] . "\"";
		if ($selected == $row["id"]) $return .= " selected";
		$return .= ">";
		$list = explode("--", $display);
		$temp = "";
		foreach ($list as $count => $field) {
			if ($temp != "") $temp .= ", ";
			$temp .= $row[$field];
		}
		$return .= $temp . "</div>";
	}
	db_close();
	
	return $return;	
	
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Extract Date Parts
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function explode_date($date) {
	
	$return = array("year" => null, "month" => null, "day" => null, "time" => null);
	$date = str_replace(" ", "-" , $date);
	$dateArray = explode("-", $date);
	
	if (isset($dateArray[0])) {
		$return["year"] = $dateArray[0];
	} 
	if (isset($dateArray[1])) {
		$return["month"] = $dateArray[1];
	} 
	if (isset($dateArray[2])) {
		$return["day"] = $dateArray[2];
	}  
	if (isset($dateArray[3])) {
		$return["time"] = $dateArray[3];
	}
	
	return $return;

}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Combine Date Parts
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function implode_date($day, $month, $year) {
	
	$return = $year."-".$month."-".$day;	
	return $return;
	
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Retrieve a name value
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function name_from_id($id, $id_label, $name_label, $table) {
	
	$sql = "SELECT " . $name_label . " FROM " . $table . " WHERE " . $id_label . " = " . $id;
	connectDatabase();
	$query = db_query($sql);
	$result = db_fetch_array($query);
	db_close();
	
	return $result[$name_label];
	
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Generate a new keycode
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function generate_keycode($type = "project") {
	
	$test = false;
	$newkey = "";
	
	while ($test == false) {
		
	//	Ordered parts
		$newkey = ucfirst(substr($type,0,1));
		$unixdate = time();
		$newkey .= $unixdate;
	//	Random parts
		$length = 10;
		$characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-";
		$string = "";
		for ($p = 0; $p < $length; $p++) {
			$rnum = mt_rand(0, 36);
			$string .= substr($characters, $rnum, 1);
		}
		$newkey .= $string;
		
	//	Check that the new key isn't a duplicate
		connectDatabase();
		$sql = "SELECT id FROM account WHERE keycode = \"" . $newkey . "\";";
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
	
	//	If key isn't a duplicate use it
		if ($exists == 0) {
			$test = true;
		}
		
	}
	
	return $newkey;

}
?>