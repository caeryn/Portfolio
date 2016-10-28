<?
/******************************************************************************************************************************
BRIDGES > IACL

Version:	1.0

Script:  	logistics.php

Purpose:	Handle database connections and queries

Requires:	none

Optional:	varies

Created: 	2011-09-03, Caeryn Price

Updated: 	2011-10-02, Caeryn Grant

******************************************************************************************************************************/

/******************************************************************************************************************************
VARIABLES
******************************************************************************************************************************/
	define ("DBHOST","localhost"); 
	define ("DBNAME","blah"); 
//	General Connection
	define ("DBUSER","blah"); 
	define ("DBPASS","blah"); 
//	Admin Connection
	define ("DBUSER_ADMIN","blah"); 
	define ("DBPASS_ADMIN","blah"); 

/******************************************************************************************************************************
FUNCTIONS
******************************************************************************************************************************/

function connectDatabase($user = "") {

	if ($user == "admin") {
		$connection = mysql_connect(DBHOST, DBUSER_ADMIN, DBPASS_ADMIN) or die(mysql_error());
	} else {
		$connection = mysql_connect(DBHOST, DBUSER, DBPASS) or die(mysql_error());
	}
	mysql_select_db(DBNAME, $connection) or die('Unable to locate requested connection table');

}

function db_query($sql) {	
	return mysql_query($sql);
}

function db_fetch_assoc($result) {
	return mysql_fetch_assoc($result);	
}

function db_fetch_array($result) {
	return mysql_fetch_array($result);
}

function db_close() {
	mysql_close();
}

function db_affected_rows() {
	return mysql_affected_rows();
}

function db_num_rows($result = "null") {
	return mysql_num_rows($result);
}


function db_list_fields($db, $tbl, $conn) {	
	return mysql_list_fields($db, $tbl, $conn);	
}

function db_insert_id() {	
	return mysql_insert_id();
}

function sanitize($text) {
	
	$text = str_replace("&", '__AMP__', $text);
	$text = str_replace('"', '__QUOTE__', $text);
	$text = str_replace(chr(39), '__APOS__', $text);
	$text = str_replace("<", '__LTHAN__', $text);
	$text = str_replace(">", '__GTHAN__', $text);
	$text = str_replace("/", '__FDSLSH__', $text);
	$text = str_replace("\\", '__BKSLSH__', $text);
	
	return $text;
}

function desanitize($text) {
	
	$text = str_replace('__FDSLSH__', "/", $text);
	$text = str_replace('__BKSLSH__', "\\", $text);
	$text = str_replace('__QUOTE__', '"', $text);
	$text = str_replace('__APOS__', "'", $text);
	$text = str_replace('__LTHAN__' , "<", $text);
	$text = str_replace('__GTHAN__' , ">", $text);
	$text = str_replace('__AMP__', "&", $text);
	
	return $text;
}	

?>