<?
class user {

	var $id;
	var $name_first;
	var $name_last;
	var $username;
	var $password;
	var $allowadmin;
	
	var $projectlist;
	var $group;
	var $grouplist;
	var $grades;
	var $searchresults;
	var $errormsg;


/******************************************************************************************************************************
OBJECT FUNCTIONS
******************************************************************************************************************************/

/* SETUP OBJECT */
	function User($id="") {
	
		$this->allowadmin = 0;
	
		if (!empty($id)) {
		
			$this->id = $id;
			
			connectDatabase();
			$sql = "SELECT id, name_first, name_last, username FROM user WHERE id = " . $this->id . ";";
			$query = db_query($sql);
			$result = db_fetch_array($query);
			db_close();
			
			$this->name_first = desanitize($result["name_first"]);
			$this->name_last = desanitize($result["name_last"]);
			$this->username = desanitize($result["username"]);
			$this->errormsg = null;
				
			connectDatabase();
			$sql = "SELECT id FROM user_admin WHERE user = " . $this->id . ";";
			$query = db_query($sql);
			$admin = db_affected_rows();
			db_close();
			
			if ($admin > 0) $this->allowadmin = true;		
		
		}
		
	}

/* NEW USER */
	function create_user($name, $pass) {
	
		$return = false;
		
		connectDatabase();	   
		$sql = "SELECT id FROM user WHERE username=\"$name\";";
		$query = db_query($sql) or die("Error with query: $sql<br />");
		$existing = db_affected_rows();
		db_close();
		
		if ($existing > 0) {
			$this->errormsg = "The user name you requested is already in use.  Please select another user name and try again.";
		} else {
			connectDatabase();	
			$sql = "INSERT INTO user (username, password) VALUES (\"$name\", \"$pass\");";
			$query = db_query($sql) or die("Error with query: $sql<br />");
			$this->id = db_insert_id();
			$return = true;
			db_close();
		}
		
		return $return;
	
	}
 	
/* FIND USER */
	function get_user_from_participant($participant_id) {
	
		$return = false;
		
		connectDatabase();
		$sql = "SELECT user FROM participant WHERE id = $participant_id;";
		$query = db_query($sql);
		$result = db_fetch_array($query);
		db_close();
			
		if (!is_null($result["user"])) {
		
			$this->id = $result["id"];
		
			connectDatabase();
			$sql = "SELECT name_first, name_last FROM user WHERE id = " . $this->id . ";";
			$query = db_query($sql);
			$result = db_fetch_array($query);
			db_close();
			
			$this->name_first = desanitize($result["name_first"]);
			$this->name_last = desanitize($result["name_last"]);	   
			$this->errormsg = null;
			
			$return = true;
		
		} else {
			
			$this->errormsg = "Participant was not found.  Please try again.";
			
		}
	
		return $return;
	
	}
 	
/* SET USER */
	function set_user($id = "", $name = "", $pass = "") {
	   
		$return = false;
		$sql = "";
		
		if (!empty($id)) {
			$sql = "SELECT id, name_first, name_last, username FROM user WHERE id = $id;";
		} elseif (!empty($name) && !empty($pass)) {
			$sql = "SELECT id, name_first, name_last, username FROM user WHERE username = \"" . trim($name) . "\" AND password = \"" . trim($pass) . "\";";
		}

		if (!empty($sql)) {	
    
			connectDatabase();
			$query = db_query($sql);
			$result = db_fetch_array($query);
			$exists = db_affected_rows();
			db_close();
	   
			if ($exists > 0) {
			
				$this->id = $result["id"];
				$this->name_first = desanitize($result["name_first"]);
				$this->name_last = desanitize($result["name_last"]);
				$this->username = desanitize($result["username"]);		   
				$this->errormsg = null;
				
				connectDatabase();
				$sql = "SELECT id FROM user_admin WHERE user = " . $this->id . ";";
				$query = db_query($sql);
				$admin = db_affected_rows();
				db_close();
				
				if ($admin > 0) $this->allowadmin = true;			 
			
				$return = true;
			
			} else {
			
				$this->errormsg = "User name and/or password did not match our files.  Please try again.";
			
			}
		  
		} else {
		
			$this->errormsg = "User name and/or password were missing.  Please try again.";
		
		}

	   return $return;
    
	}
	   
/* UPDATE USER */
	function update_user() {
	
		$sql = "UPDATE user SET 
			name_last = \"" . sanitize($this->name_last) . "\", 
			name_first = \"" . sanitize($this->name_first) . "\",
			username = \"" . sanitize($this->username) . "\", 
			password = \"" . sanitize($this->password) . "\" 
			where id = " . $this->id;
		connectDatabase("admin");
		$query = db_query($sql) or die("Error with query: $sql");
		db_close();
	
	}


/******************************************************************************************************************************
OUTPUT FUNCTIONS
******************************************************************************************************************************/

/* Get user role for a specific project */
	function get_project_role($project_id) {
		
		$return = "";
		
		connectDatabase();
		$sql = "SELECT class_role.name AS role FROM participant LEFT JOIN class_role ON participant.class_role = class_role.id WHERE participant.user = " . $this->id . " AND participant.project = " . $project_id . ";";
		$query = db_query($sql);
		$result = db_fetch_array($query);
		$exists = db_affected_rows();
		db_close();
		
		if ($exists > 0) {
			$return = $result["role"];
		}
		
		return $return;
		
	}

/* Get user project list */
	function get_projectlist() {
	
		$return = "";
		$exists = 0;
		
		connectDatabase();
		$sql = "SELECT project.id, project.name, project.detail, project.date_due FROM participant LEFT JOIN project ON participant.project = project.id WHERE participant.user = " . $this->id . ";";
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
	
		if ($exists > 0) {
			
			$return .= "
			<form name=\"userprojects\" method=\"post\">";

			while ($row = db_fetch_array($query)) {
			 
				$detail_array = explode("\n", $row["detail"]);
				if (count($detail_array) > 1) $detail_display = desanitize($detail_array[0]) . "...";
				elseif (count($detail_array) == 1) $detail_display = desanitize($detail_array[0]);
				
				$return .= "
					<div class=\"form_block\">
						<span class=\"form_title\"><input type=\"radio\" name=\"project_id\" value=\"" . $row["id"] . "\"></span>
						<span class=\"form_title\">" . $row["name"] . "</span>
					</div>\n";
					if (!is_null($row["detail"]) && !empty($row["detail"])) $return .= "<div class=\"radionote\">$detail_display</div>";

			}

			$return .= "
				<div class=\"form_block\">
					<span class=\"form_submit_inline\"><input name=\"submit\" type=\"submit\" value=\"View Project\" class=\"form_button\"></span>
				</div>
				</form>";
		
		}
		
		if ($return == "") $return = "<p class=\"error\">You do not have any projects set up yet.  Please enter a keycode below to add a new project.</p>";
		return $return;
	
	}
 
/* Get project participants that might match this user */
	function get_participants($project_id) {
	
		$return = "";
		$exists = 0;
		$match_first = substr($this->name_first, 0, 1);
		$match_last = substr($this->name_last, 0, 1);
		
		connectDatabase();
		$sql = "SELECT id, name_last, name_first, `group` FROM participant WHERE name_first LIKE \"$match_first%\" AND name_last LIKE \"$match_last%\" AND project = $project_id;";
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
			   
		if ($exists > 0) {
		//	matching students found - show them the matches
			
			$return .= "
				<form name=\"projectparticipants\" method=\"post\">
				<input name=\"project_id\" type=\"hidden\" value=\"$project_id\">";
					  
			while ($row = db_fetch_array($query)) {

				$return .= "
					<div class=\"form_block\">
						<span class=\"form_title\"><input type=\"radio\" name=\"participant_id\" value=\"" . $row["id"] . "\"></span>
						<span class=\"form_title\">" . ucfirst($row["name_last"]) . ", " . ucfirst($row["name_first"]) . "</span>
					</div>\n";
					
			}
					  
			$return .= "
				<div class=\"form_block\">
					<span class=\"form_submit_below\"><input name=\"submit\" type=\"submit\" value=\"Add Me To This Project\" class=\"form_button\"></span>
				</div>
				</form>";
				
			   
		} else {
		//	no matching students found - show them all unassigned students from the class
		
			connectDatabase();
			$sql = "SELECT id, name_last, name_first, `group` FROM participant WHERE user = 0 AND project = $project_id;";
			$query = db_query($sql);
			$exists = db_affected_rows();
			db_close();
			
			if ($exists > 0) {
			//	matching students found - show them the matches
				
				$return .= "
					<form name=\"projectparticipants\" method=\"post\">
					<input name=\"project_id\" type=\"hidden\" value=\"$project_id\">";
						  
				while ($row = db_fetch_array($query)) {
	
					$return .= "
						<div class=\"form_block\">
							<span class=\"form_title\"><input type=\"radio\" name=\"participant_id\" value=\"" . $row["id"] . "\"></span>
							<span class=\"form_title\">" . ucfirst($row["name_last"]) . ", " . ucfirst($row["name_first"]) . "</span>
						</div>\n";
						
				}
						  
				$return .= "
					<div class=\"form_block\">
						<span class=\"form_submit_below\"><input name=\"submit\" type=\"submit\" value=\"Add Me To This Project\" class=\"form_button\"></span>
					</div>
					</form>";
					
				   
			} else {
			//	no students listed for this project yet - or this project doesn't exist - error message
				$return .= "
				<div class=\"error\">
					There are no students listed for this project yet.  Please contact your professor for further assistance.
				</div>";
			   
			}
		}
		return $return;
	    
	}
    
/* add this user to a project */
	function add_user_project($project_id, $participant_id) {
	
		$return = false;
		$exists = 0;
		
	//	Is this participant associated with this project?
		connectDatabase();
		$sql = "SELECT id FROM participant WHERE id = " . $participant_id . " AND project = " . $project_id . ";";
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
		
		//	Yes    
			if ($exists > 0) {
			
				$sql = "UPDATE participant SET user = " . $this->id . " WHERE id = " . $participant_id . ";"; 
				connectDatabase();
				$query = db_query($sql);
				db_close();
				
				$this->errormsg = null;	
				$return = true;
		
		//	No
			} else {
				
				$this->errormsg = "The project participant file you requested was not recognized.  Please try again.";
			
			}
		
		return $return;
	
	}

/* Save the grades this student is giving his groupmates */
    function save_grades($project_id, $grades) {

	//	Variables
		$ref_participants = array();
		$ref_reviewcategory = array();
		$return = false;
		$exists = 0;
		$myTime = date("Y-m-d");
		
	//	Were there any grades to save?
		if (count($grades) > 0) {

		//	Database queries
			connectDatabase();
			//	Participant id
				$sql_user = "SELECT id FROM participant WHERE user = " . $this->id . " AND project = " . $project_id . ";";
				$query_user = db_query($sql_user);
				$result = db_fetch_array($query_user);
			db_close();
	
		//	Did we find a user?
			if (!is_null($result["id"])) {
	
			//	Variables
				$grade_from = $result["id"];
				$grades_validate = array(); // Grades validated
	
			//	Database queries
				connectDatabase();
				//	Participant reference
					$sql_participants = "SELECT id, name_last, name_first FROM participant WHERE project = " . $project_id . ";";
					$query_participants = db_query($sql_participants);
				//	Grade category reference
					$sql_reviewcategory = "SELECT id, name FROM class_review;";
					$query_reviewcategory = db_query($sql_reviewcategory);
				db_close();
	
			//	Build reference arrays
				while ($row = db_fetch_assoc($query_participants)) {
					$ref_participants[$row["id"]] = desanitize($row["name_first"]) . " " . desanitize($row["name_last"]);
				}
				while ($row = db_fetch_assoc($query_reviewcategory)) {
					$ref_reviewcategory[$row["id"]] = desanitize($row["name"]);
				}
				
			//	Validate grades
				foreach ($grades as $grade_to => $gradevalues) {
				//	grades [ participant_id ] [ class_review_id ] = score
				
					$grades_validate[$grade_to]["name"] = $ref_participants[$grade_to];
					$grades_validate[$grade_to]["errors"] = "";
					$grades_validate[$grade_to]["count"] = 0;
	
				//	Individual Values
					foreach ($gradevalues as $category => $value) {
						if (!is_numeric($value)) {
						//	non-numeric
							$grades_validate[$grade_to]["errors"] .= "<div class=\"error\">Grade " . $ref_reviewcategory[$category] . " ($value) for " . $grades_validate[$grade_to]["name"] . " is not a number.</div>";
						} elseif ($value < 0) {
						//	negative number
							$grades_validate[$grade_to]["errors"] .= "<div class=\"error\">Grade " . $ref_reviewcategory[$category] . " ($value) for " . $grades_validate[$grade_to]["name"] . "  is a negative number.</div>";
						} else {
						//	add to total & array
							$grades_validate[$grade_to]["grade_sum"] = $grades_validate[$grade_to]["grade_sum"] + $value;
							$grades_validate[$grade_to]["new"][$category] = $value;
							$grades_validate[$grade_to]["count"]++;
						}
					}
					
				//	Aggregate Values
					if ($grades_validate[$grade_to]["grade_sum"] > 50) {
						$grades_validate[$grade_to]["errors"] .= "<div class=\"error\">Grade total for " . $grades_validate[$grade_to]["name"] . " is greater than 50 points.</div>";
					}					
					
				//	Stack zeros in empty grade categories if grades are present in other categories
					if ($grades_validate[$grade_to]["count"] > 0 && $grades_validate[$grade_to]["count"] < count($ref_reviewcategory)) {
						foreach ($ref_reviewcategory as $category_id => $category_name) {
							if (!isset($grades_validate[$grade_to]["new"][$category_id])) {
								$grades_validate[$grade_to]["new"][$category_id] = 0;
							}
						}
					}
					
				}
				
			//	Submit validated grades
				if (isset($grades_validate)) {
					connectDatabase();
					foreach ($grades_validate as $grade_to => $validation_results) {
					// grades [ participant_id ] [ class_review_id ] = score
						if ($validation_results["errors"] == "") {
							foreach ($validation_results["new"] as $class_review => $gradevalue) {
								$sql = "INSERT INTO participant_review (participant_from, participant_to, class_review, score, date_reviewed)
										VALUES ($grade_from, $grade_to, $class_review, $gradevalue, \"$myTime\");";
								$query = db_query($sql);
								$return = true;
							}
						} else {
							$this->errormsg .= $validation_results["errors"];
							$return = false;
						}
					}
					db_close();
				}
				
			
		//	No user found
			} else {
			    $this->errormsg = "The project participant file you requested was not recognized.  Please try again.";
			    $return = false;
			}
			
	//	No grades passed
		} else {
		    $this->errormsg = "No grades were submitted.  Please try again.";
		    $return = false;
		}

	//	Return value
		return $return;
    
	}

/* Get user details */
	function get_userdetail() {
		
		$return = "";
		
	//	Get participant details
		connectDatabase();
		$sql = "SELECT * FROM participant WHERE id = " . $this->id . ";";
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
		
		if ($exists > 0) {
			$result = db_fetch_assoc($query);
			$return .= "<div>" . $result["name_last"] . ", " . $result["name_first"] . "</div>
				<div class=\"fieldnote\">Group " . $result["group"] . "</div>";
		} else {
			$return = false;
		}
		
		return $return;
	
	}

/* Get participant details */
	function get_participant_id($project_id) {
		
		$return = false;
	   
    //	Get this user's participant id for the given project
		connectDatabase();
		$sql = "SELECT id FROM participant WHERE user = " . $this->id . " AND project = " . $project_id . ";";
		$query = db_query($sql);
		$exists = db_affected_rows();
		db_close();
	
		if ($exists > 0) {
			
			$result = db_fetch_assoc($query);
			$return = $result["id"];
			
		}
		
		return $return;
	
	}


/******************************************************************************************************************************
INPUT FUNCTIONS
******************************************************************************************************************************/
	function set_name_first($new_value) {
	
		$this->name_first = $new_value;
	
	}
	
	
	function set_name_last($new_value) {
	
		$this->name_last = $new_value;
	
	}
	
	
	function set_username($new_value) {
	
		$this->username = $new_value;
	
	}
	
	
	function set_password($new_value) {
	
		$this->password = $new_value;
	
	}
    
}
?>