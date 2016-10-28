<?
class account {

	var $id;
	var $name;
	var $keycode;
	var $detail;
	var $projectcount;
	var $date_start;
	
	var $projectlist;
	var $errormsg;
	var $handout;
	

/******************************************************************************************************************************
OBJECT FUNCTIONS
******************************************************************************************************************************/

/* SETUP OBJECT */
	function Account($id="") {
	
		if (!empty($id)) {
		
			$this->id = $id;
			
			connectDatabase();
			$sql = "SELECT * FROM account WHERE id = " . $this->id . ";";
			$query = db_query($sql);
			$result = db_fetch_array($query);
			db_close();
			
			$this->name = desanitize($result["name"]);
			$this->keycode = $result["keycode"];
			$this->detail = desanitize($result["detail"]);
			$this->projectcount = $result["projectcount"];
			$this->date_start = $result["date_start"];
			$this->errormsg = null;
		
		}
		
	}
    
/* FIND OBJECT */
	function get_account($id = "", $keycode = "") {
    
		$return = false;
		$exists = 0;
		$sql = "";
	   
		if ($id == "" && $keycode != "") {
			$sql = "SELECT * FROM account WHERE keycode = \"" . $keycode . "\";";
		} elseif ($id != "") {
			$sql = "SELECT * FROM account WHERE id = " . $id . ";";
		}

		if ($sql != "") {
			connectDatabase();
			$query = db_query($sql);
			$exists = db_affected_rows();
			db_close();
  
			if ($exists > 0) {
				$result = db_fetch_assoc($query);
				$this->id = $result["id"];
				$this->name = desanitize($result["name"]);
				$this->keycode = $result["keycode"];
				$this->detail = desanitize($result["detail"]);
				$this->projectcount = $result["projectcount"];
				$this->date_start = $result["date_start"];
				$this->errormsg = null;
				$return = true;
			}
		} 
	   
		return $return;
	   
	}

/* CREATE OBJECT */
	function create_account($name) {
    
		$return = false;
		$name = sanitize($name);
		$myTime = date("Y-m-d");
		$exists = 0;
		$newkey = generate_keycode("account");
		
		connectDatabase();	   
		$sql = "INSERT INTO account (name, keycode, date_start) VALUES (\"$name\", \"$newkey\", \"$myTime\");";
		$query = db_query($sql) or die("Error with query: $sql<br />");
		$exists = db_insert_id();
		db_close();
		
		if ($exists != 0) {
			$this->id = $exists;
			$this->keycode = $newkey;
			$this->name = desanitize($name);
			$this->date_start = $myTime;
			$return = true;
		}
	 
		return $return;
    
	}

/* UPDATE OBJECT */
	function update_account() {
    
		$sql = "UPDATE account SET 
			name = \"" . sanitize($this->name) . "\", 
			detail = \"" . sanitize($this->detail) . "\", 
			projectcount = \"" . $this->projectcount . "\" 
			where id = " . $this->id;
		connectDatabase("admin");
		$query = db_query($sql) or die("Error with query: $sql");
		db_close();
    
	}


/******************************************************************************************************************************
OUTPUT FUNCTIONS
******************************************************************************************************************************/
    
/* Get projects associated with this account */
	function get_projects() {
    
		$return = "";
		$exists = 0;
		
		connectDatabase();
		$sql = "SELECT * FROM project WHERE account = \"" . $this->id . "\" ORDER BY name;";
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
		
		if ($exists > 0) {
			
			$return .= "<ul>";
			
			while ($row = db_fetch_array($query)) {
		
				$return .= "<li>" . $row["name"] . "</li>\n";
   
			}
			
			$return .= "</ul>";
		
		}
		
		return $return;
    
	}
    
/* Check remaining module balance on this account */
	function check_remaining_balance() {
    
		$return = false;
		
		connectDatabase();
		$sql = "SELECT count(*) AS recordcount FROM project WHERE account IN (" . $this->id . ");";
		$query = db_query($sql);
		$result = db_fetch_assoc($query);
		db_close();
		
		if ($result["recordcount"] < $this->projectcount) {
		
			$return = true;  
		
		}
		
		return $return;
    
	}
 
/* Get list of accounts that might match the provided search string */
	function search_accounts($searchstring = "") {

		$return = "";
		$sql = "";
		$exists = 0;

		if ($searchstring == "") {
			$sql = "SELECT id, name FROM account ORDER BY name;";
		} else {
			$sql = "SELECT id, name FROM account WHERE name LIKE \"%$searchstring%\" ORDER BY name;";
		}

		connectDatabase();
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
			   
		if ($exists > 0) {

			$return .= "
				<form name=\"accounts\" method=\"post\">";

			while ($row = db_fetch_array($query)) {

				$return .= "
					<div class=\"form_block\">
						<span class=\"form_title\"><input type=\"radio\" name=\"account_id\" value=\"" . $row["id"] . "\"></span>
						<span class=\"form_title\">" . $row["name"] . "</span>
					</div>\n";

			}

			$return .= "
				<div class=\"form_block\">
					<span class=\"form_submit_inline\"><input name=\"submit\" type=\"submit\" value=\"View Account\" class=\"form_button\"></span>
				</div>
				</form>";

		}

		if ($return == "") $return = "<p class=\"error\">No accounts match your request.  Please try again or add a new account.</p>";
		return $return;
	    
	}
	
/* Get account add/edit form */
	function get_account_form() {
		
		if (is_null($this->id) || empty($this->id)) $account_id = "new";
		else $account_id = $this->id;
		
		$return = "
			<form name=\"account\" method=\"post\">
			<input type=\"hidden\" name=\"account_id\" value=\"$account_id\">
			<div class=\"content\">
				<div class=\"form_title_below\">Name</div>
				<div class=\"form_field_below\"><input type=\"text\" name=\"name\" size=\"50\" value=\"" . $this->name . "\"></div>
				<div class=\"form_title_below\">Keycode</div>";
		if (!is_null($this->keycode) && $this->keycode != "") {
			$return .= "<div class=\"form_field_below\">" . $this->keycode . "</div>";
		} else {
			$return .= "<div class=\"form_field_below\"><i>Value will be assigned when the account is saved.</i></div>";
		}
		$return .= "
					<div class=\"form_title_below\">Details</div>
					<div class=\"form_field_below\"><textarea name=\"detail\" rows=\"10\" cols=\"25\">" . $this->detail . "</textarea></div>
					<div class=\"form_title_below\">Number of Project Modules</div>
					<div class=\"form_field_below\"><input type=\"text\" name=\"projectcount\" size=\"5\" value=\"" . $this->projectcount . "\"></div>
					<div class=\"form_title_below\">Date Account Created</div>";
		if (!is_null($this->date_start) && $this->date_start != "") {
			$return .= "<div class=\"form_field_below\">" . $this->date_start . "</div>";
		} else {
			$return .= "<div class=\"form_field_below\"><i>Value will be assigned when the account is saved.</i></div>";
		}

		$return .= "
				<div class=\"form_submit_below\"><input name=\"submit\" type=\"submit\" value=\"Save Account\" class=\"form_button\"></div>
			</div>
			</form>";
			
		return $return;
		
	}
	
/* Get account search form */
	function get_searchform() {
		
		$return = "
			<form name=\"searchaccount\" method=\"post\">
			<div class=\"form_block\">
				<div class=\"form_title\">Search account names for...</div>
				<span class=\"form_field_below\"><input type=\"text\" name=\"name\" size=\"50\"></span>
				<span class=\"form_submit_below\"><input name=\"submit\" type=\"submit\" value=\"Search Accounts\" class=\"form_button\"></span>
			</div>
			</form>";
			
		return $return;
		
	}
	
/* Get account add form */
	function get_addform() {
		
		$return = "
			<form name=\"addaccount\" method=\"post\">
			<div class=\"inline_submit\"><input name=\"submit\" type=\"submit\" value=\"Add Account\" class=\"form_button\"></div>
			</form>";
			
		return $return;
		
	}

/* Get account details */
	function get_account_array() {
		
		$return = array();
	
		$return["account_id"] = $this->id;
		$return["name"] = $this->name;
		$return["detail"] = $this->detail;
		$return["keycode"] = $this->keycode;
		$return["projectcount"] = $this->projectcount;
		$return["date_start"] = $this->date_start;
		
		return $return;
	
	}

/******************************************************************************************************************************
INPUT FUNCTIONS
******************************************************************************************************************************/

 
	function set_name($new_value) {
	
		$this->name = stripslashes($new_value);
	
	}
    
    
	function set_detail($new_value) {
	
		$this->detail = stripslashes($new_value);
	
	}
    
    
	function set_projectcount($new_value) {
	
		$this->projectcount = $new_value;
	
	}

}
?>