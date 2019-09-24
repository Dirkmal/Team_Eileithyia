<?php
	class Ctrl{
		private $db_server = "localhost";
		private $db_user = "mafmifeo_profojo";
		private $db_pass = "pR0f3550R0j0";
		private $db_name = "mafmifeo_mafmife";
		//private $db_user = "root";
		//private $db_pass = "";
		//private $db_name = "mafmife";
		public $db_r_table = "reports";	
		public $db_i_table = "immuno";
		public $db_p_table = "patients";
		public $db_a_table = "accounts";
		public $db_table = "";
		public $con = "";
		public $result = 0; // default value
		public $attempts_left = "";
		public $message_codes = array("Hospital" => "HN", "Success" => "SC", "Error" => "ER");
		public $messages = array("HN" => "Hospital number is registered to a different patient",
								 "SC" => "Operation successful",
								 "ER" => "An error occured",
								 "IC" => "Invalid credentials",
								 "ATL" => "Attempts left",
								 "LCK" => "Your account has been locked, please contact the system administrator",
								 "PSWDCHNG" => "Password changed successfully, login",
								 "CHNGPSWD" => "Change user's password"
		);
		public $colors = array("inf" => "info", "sc" => "success", "wrn" => "warning", "dng" => "danger");
		public $hyde = "";   
		//TODO use the below statement to get age, change age to d.o.b
		//SELECT name, birth, TIMESTAMPDIFF(YEAR,birth,CURDATE()) AS age FROM your_table;
		public function __construct() {
			$this->con = new mysqli($this->db_server,$this->db_user,$this->db_pass,$this->db_name);
		}

		function login($username, $password){
			@session_start();
			//$_SESSION['log_att'] = 0;
			$this->setTable($this->db_a_table);

			//log out any logged in user
			if(isset($_SESSION['mamife_user'])){
				$this->logout();
			}

		    $query = $this->con->prepare("SELECT id, access_type, account_status, password FROM $this->db_table WHERE username = ?");
	        
			$query->bind_param('s', $username); 			
			$query->execute();
			$query->store_result();

			if($query->num_rows > 0){
				$query->bind_result($id, $a_level, $acc_stat, $gotten_password); 
				$query->fetch();
				$status = "1";
			   
			   	if($acc_stat){//if the account status is 1, i.e not locked
			   		if(md5($password) === $gotten_password){//if the passwords match
						// Set session name and access levels
						$_SESSION['mamife_user'] = $username;
						$_SESSION['mamife_access'] = $a_level;
						$_SESSION['log_att'] = 0;
						
						// Update the account's last_login column
						$query = $this->con->prepare("UPDATE $this->db_table SET last_login = NOW(), status = ? WHERE id = ?");
						$query->bind_param('dd', $status, $id); 
						$query->execute();
						
						header('location: index.php');
					}else{	//Passwords do not match 							
						@$_SESSION['log_att']++;
						$this->attempts_left = 3 - $_SESSION['log_att'];
						
						$this->alert("IC");	
						$this->alert("ATL");	//attempts left

						if($_SESSION['log_att'] >= 3){
							//if failed login attempts are up to 3, lock the account
							$query = $this->con->prepare("UPDATE $this->db_table SET account_status = 0 WHERE username = ?");
							$query->bind_param('s', $username); 
							if($query->execute()){
								$this->alert( "LCK");
								$_SESSION['log_att'] = 0;
							}
						}
					}		    
				}else{ //account is locked
					$this->alert("LCK");					
				}
			}else{	//username does not exist, logout any currently logged in user
				$this->alert("IC");				
				$this->logout();
			}
	    }

		function logout(){
			@session_start();
			if(isset($_SESSION['mamife_user'])){
				$this->setTable("accounts");
				$query = $this->con->prepare("UPDATE $this->db_table SET status = ? WHERE username = ?");
				$stat = "0";
				$query->bind_param('ds', $stat, $_SESSION['mamife_user']); 
				$query->execute();
				session_unset();
			}	    
		}

		function userAddUpdate($username, $password, $level, $fname, $lname, $update_or_insert, $id){
			$this->toUpperCase(Array(&$fname, &$lname));

			$query = "";
			switch($update_or_insert){
				case "insert":
					$query = $this->con->prepare("INSERT INTO $this->db_a_table (username, password, access_type, first_name, last_name) VALUES (?, md5(?), ?, ?, ?)");
					$query->bind_param('ssdss', $username, $password, $level, $fname, $lname);
					break;
				
				case "update":
					$query = $this->con->prepare("UPDATE $this->db_a_table SET username = ?, password = md5(?), access_type = ?, first_name = ?, last_name = ? WHERE id = ?");
					$query->bind_param('ssdsss', $username, $password, $level, $fname, $lname, $id);
					break;
			}			
			
			$query->execute();
			$this->alert("SC");
		}
		
		function deleteUser($user_id){
			$query = $this->con->prepare("DELETE FROM $this->db_a_table WHERE id = ?");
			$query->bind_param('d', $user_id);

			return $this->queryRunner($query);
		}

		function access_control($access_level){
			ob_start();
		    @session_start();

		    if(!isset($_SESSION['mamife_user'])){
		    	header('location: login.php');
		    }else{
		    	if($_SESSION['mamife_access'] == $access_level || $_SESSION['mamife_access'] == 5 || $access_level == 0){
		    		//0 = world
				//5 = admin
				//do nothing
		    	}else{
		    		header('location: index.php');
		    	}
		    }
		}

		function connect(){
			$con = new mysqli($this->db_server,$this->db_user,$this->db_pass,$this->db_name);
			return $con; //return the connection to the instance of the class
		}

		function setTable($table_name){			
			$table_name = strtolower($table_name);

			$this->db_table = $table_name;	 		
		}

		function getReportFormat($report_type, $get_type = 0){			
			$format = "X-";
			$report_type = strtolower($report_type);

			switch($report_type){	    		
				case 'histology':
					$format = "H-";
					break;
				case 'cytology':
					$format = "C-";
					break;	 
				case 'immuno': case 'immunohistochemistry':
					$format = 'IHC-';
					break;
			}
		
			return $format;
		}

		function getFullName($surname, $first_name, $other_name){
			return $surname." ".$first_name." ".$other_name;
		}
		
		function getPatientData($id){
			$this->setTable($this->db_p_table);
			$query = $this->con->query("SELECT * FROM $this->db_table WHERE id = $id");                                                
														
			while($r = $query->fetch_assoc()){            
				$id = $r['id'];
				$hospital_num = $r['hospital_num'];
				$surname = $r['surname'];
				$first_name = $r['first_name'];
				$other_name = $r['other_name'];
				$gender = $r['gender'];
				$age = $r['age'];
			}

			return $bio_data = array('id' => $id, 'hnum' => $hospital_num, 'surname' => $surname, 'first' => $first_name, 'other' => $other_name, 'gender' => $gender, 'age' => $age);
		}
		
		function queryRunner($query, $binary = 0){
			if($query->execute()){
				if($binary){
					$this->result = 1;
				}else{
					$this->result = $this->message_codes['Success'];
				}
			}else{
				if($binary){
					$this->result = 0;
				}else{
					$this->result = $this->message_codes['Error'];
				}
			}

			return $this->result;
		}

		function reportNumNowReady($report_id, $report_type, $report_num = 0){
			if($report_num === 0){
				$report_num = $this->getReportNum($report_type);
			}

			$query = $this->con->prepare("UPDATE $this->db_r_table SET report_num = ?, nyr = 0 WHERE id = ?");
			
			$query->bind_param('sd', $report_num, $report_id);
			
			return $this->queryRunner($query, 1);
		}

		function deleteRedundant($record_id){
			$query = $this->con->prepare("DELETE FROM $this->dr_r_table WHERE id = ?");
			
			$query->bind_param('d', $record_id);
			
			return $this->queryRunner($query);
		}

		function updateRecordReception($record_id, $req_con, $nature, $clinical_dets, $auth_con, $immuno = false){
			$this->toUpperCase(Array(&$req_con, &$auth_con));
			$this->setTable($this->db_r_table);

			if($immuno){
				$this->setTable($this->db_i_table);
			}

			$query = $this->con->prepare("UPDATE $this->db_table SET req_consultant = ?, nature = ?, clinical_details = ?, auth_con = ? WHERE id = ?");

			$query->bind_param('ssssd', $req_con, $nature, $clinical_dets, $auth_con, $record_id);

			return $this->queryRunner($query);
		}

		function updateRecordTypist($record_id, $gross_or_refReport, $clinical_dets, $typist_username, $immuno = false){
			$this->toUpperCase(Array(&$typist_username));
			$this->setTable($this->db_r_table);
			$column = "gross_desc";

			if($immuno){
				$this->setTable($this->db_i_table);
				$column = "ref_report";
			}
			
			$query = $this->con->prepare("UPDATE $this->db_table SET $column = ?, clinical_details = ?, typed = 1, typed_by = ? WHERE id = ?");

			$query->bind_param('sssd', $gross_or_refReport, $clinical_dets, $typist_username, $record_id);

			return $this->queryRunner($query);
		}
	    
		function updateRecordResident($record_id, $clinical_dets, $gross, $microscopy, $opinion, $resident, $checked_by){
			$this->toUpperCase(Array(&$resident, &$checked_by));		
			
			$query = $this->con->prepare("UPDATE $this->db_r_table SET clinical_details = ?, gross_desc = ?, microscopy = ?, opinion = ?, resident = ?, r_sign_date = NOW(), resident_checked = 1, checked_by = ? WHERE id = ?");	
			
			$query->bind_param('ssssssd', $clinical_dets, $gross, $microscopy, $opinion, $resident, $checked_by, $record_id);

			return $this->queryRunner($query);				
		}

		function residentUpdates($report_id, $date_signed_r, $immuno = false){
			$this->setTable($this->db_r_table);					

			if($immuno){
				$this->setTable($this->db_i_table);
			}

			$query = $this->con->prepare("UPDATE $this->db_table SET r_sign_date = ?, resident_checked = 1 WHERE id = ?");
			
			$query->bind_param('sd', $date_signed_r, $report_id);

			return $this->queryRunner($query, 1);
		}

		function updateRecordConsultant($record_id, $comments, $auth_con, $immuno = false){
			$this->toUpperCase(Array(&$auth_con));
			$this->setTable($this->db_r_table);					

			if($immuno){
				$this->setTable($this->db_i_table);
			}

			$query = $this->con->prepare("UPDATE $this->db_table SET comments = ?, auth_con = ? WHERE id = ?");	
		
			$query->bind_param('ssd', $comments, $auth_con, $record_id);

			return $this->queryRunner($query);				
		}

		function updateRecordAll($record_id, $ward, $req_con, $nature, $clinical_dets, $gross_or_refReport, $microscopy_or_initialHd, $opinion_or_fDiag, $resident, $comments, $auth_con, $consultant, $immuno = false, $im_report = "none"){
			$this->toUpperCase(Array(&$req_con, &$resident, &$auth_con));
			
			
			if($immuno){
				$this->setTable($this->db_i_table);
				$query = $this->con->prepare("UPDATE $this->db_table SET ward = ?, req_consultant = ?, nature = ?, clinical_details = ?, ref_report = ?, typed = 1, initial_hd = ?, f_diagnosis = ?, im_report = ?, resident = ?, comments = ?, auth_con = ?, consultant = ? WHERE id = ?");
				$query->bind_param('ssssssssssssd', $ward, $req_con, $nature, $clinical_dets, $gross_or_refReport, $microscopy_or_initialHd, $opinion_or_fDiag, $im_report, $resident, $comments, $auth_con, $consultant, $record_id);				
			}else{
				$this->setTable($this->db_r_table);				
				$query = $this->con->prepare("UPDATE $this->db_table SET ward = ?, req_consultant = ?, nature = ?, clinical_details = ?, gross_desc = ?, typed = 1, microscopy = ?, opinion = ?, resident = ?, comments = ?, auth_con = ?, consultant = ? WHERE id = ?");
				$query->bind_param('sssssssssssd', $ward, $req_con, $nature, $clinical_dets, $gross_or_refReport, $microscopy_or_initialHd, $opinion_or_fDiag, $resident, $comments, $auth_con, $consultant, $record_id);				
			}
				
			return $this->queryRunner($query);
		}

		function authoriseReport($record_id, $auth_by, $auth_con, $immuno = false){
			$this->toUpperCase(Array(&$auth_by, &$auth_con));
			$this->setTable($this->db_r_table);

			if($immuno){
				$this->setTable($this->db_i_table);
			}

			$query = $this->con->prepare("UPDATE $this->db_table SET auth_by = ?, authorisation_date = NOW(), authorised = 1 , auth_con = ? WHERE id = ?");
		
			$query->bind_param('ssd', $auth_by, $auth_con, $record_id);

			return $this->queryRunner($query);
		}

		function unauthoriseReport($record_id){//no longer in use
			$query = $this->con->prepare("UPDATE $this->db_r_table SET  authorised = 0 WHERE id = ?");
		
			$query->bind_param('d', $record_id);

			return $this->queryRunner($query);
		}

		function newRecord($patient_id, $report_type, $h_num, $ward, $r_con, $nature, $clinical_dets, $auth_con){
			$this->toUpperCase(Array(&$auth_con));
			
			$report_num = $this->getReportNum($report_type);			

			$query = $this->con->prepare("INSERT INTO $this->db_r_table (report_type, report_num, patient_id, hospital_num, ward, req_consultant, nature, clinical_details, auth_con) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
			
			$query->bind_param('ssdssssss', $report_type, $report_num, $patient_id, $h_num, $ward, $r_con, $nature, $clinical_dets, $auth_con);
							
			return $this->queryRunner($query);
		}

		function newImmuno($patient_id, $report_type, $h_num, $ward, $r_con, $nature, $clinical_dets, $auth_con){
			$this->toUpperCase(Array(&$auth_con));
			
			$report_num = $this->getReportNum($report_type);			

			$query = $this->con->prepare("INSERT INTO $this->db_i_table (report_num, patient_id, hospital_num, ward, req_consultant, nature, clinical_details, auth_con) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?)");
			
			$query->bind_param('sdssssss', $report_num, $patient_id, $h_num, $ward, $r_con, $nature, $clinical_dets, $auth_con);
							
			return $this->queryRunner($query);
		}
		
		function getMysqliError($query){
			return $query->error;
		}

		function newPatient($report_type, $hnum, $ward, $surname, $fname, $oname, $age, $gender, $occupation, $r_con, $nature, $clinical_dets, $registrar, $auth_con, $immuno = false){	    		    			
			$this->toUpperCase(Array(&$surname, &$fname, &$oname, &$registrar, &$auth_con));

			$fullname = $surname." ".$fname." ".$oname;
			
			if($this->registeredNum($hnum, "name") === "" || strtolower($hnum) === "nyr"){	
				//no patient with that hospital number or none was provided
				/**NB: If patient hospital number is not provided, such patient might be registered more than once */
				
				$query = $this->con->prepare("INSERT INTO $this->db_p_table (hospital_num, surname, first_name, other_name, age, gender, occupation, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
				$query->bind_param('ssssssss', $hnum, $surname, $fname, $oname, $age, $gender, $occupation, $registrar);
				
				if($query->execute()){
					//get last insert id of patients and use it to for column (patient_id) in report's table					
					$last_id = $this->con->insert_id;
					
					if($report_type == "Immunohistochemistry" || $report_type == "Immuno"){
						$this->newImmuno($last_id, $report_type, $hnum, $ward, $r_con, $nature, $clinical_dets, $auth_con);
					}else{
						$this->newRecord($last_id, $report_type, $hnum, $ward, $r_con, $nature, $clinical_dets, $auth_con);
					}
					
					$this->result = $this->message_codes['Success']; //Patient registered succesfully
				}else{
					$this->result = $this->message_codes['Error']; //An error occured
				}	    		
			}elseif($this->registeredNum($hnum, "name") === $fullname){	
				//hospital number is already registered					
				$patient_id = $this->registeredNum($hnum, "id");

				if($report_type == "Immunohistochemistry" || $report_type == "Immuno"){
					$this->newImmuno($patient_id, $report_type, $hnum, $ward, $r_con, $nature, $clinical_dets, $auth_con);
				}else{
					$this->newRecord($patient_id, $report_type, $hnum, $ward, $r_con, $nature, $clinical_dets, $auth_con);
				}
				
				$this->result = $this->message_codes['Success']; //New report created for already registered patient
			}else{
				//hospital number registered but names do not match
				$this->result = $this->message_codes['Hospital']; //Hospital number is registered to a different patient
			}
			
			return $this->result;
	   	}
	    //TODO how to rollback changes
	    //TODO how to update two different tables at the same time
	   	function patientChanges($id, $hnum, $surname, $fname, $oname, $age, $gender, $occupation, $registrar){
			//update patient information
			$this->toUpperCase(Array(&$surname, &$fname, &$oname, &$registrar));

			$query = $this->con->prepare("UPDATE $this->db_p_table SET hospital_num = ?, surname = ?, first_name = ?, other_name = ?, age = ?, gender = ?, occupation = ?, registered_by = ? WHERE id = ?");
				
			$query->bind_param('sssssssss', $hnum, $surname, $fname, $oname, $age, $gender, $occupation, $registrar, $id);
			
			if($query->execute()){
				$report = $this->con->prepare("UPDATE $this->db_r_table SET hospital_num = ? WHERE patient_id = ?");
				$report->bind_param('ss', $hnum, $id);				

				if($report->execute()){
					$this->result = $this->message_codes['Success']; //"Patient information updated"
				}else{
					$this->result = $this->message_codes['Error']; //An error occured
				}
			}else{
				$this->result = $this->message_codes['Error']; //An error occured
			}

			return $this->result;
		}

		function updatePatient($id, $hnum, $surname, $fname, $oname, $age, $gender, $occupation, $registrar){
			//performs checks before calling "patientChanges()" to make actual updates to patient information
			$fullname = $surname." ".$fname." ".$oname;
			
			if($this->registeredNum($hnum, "name") == $fullname || $this->registeredNum($hnum, "name") == ""){
				//if the hospital number belongs to the patient or no one is saved with that hospital number
				$this->result = $this->patientChanges($id, $hnum, $surname, $fname, $oname, $age, $gender, $occupation, $registrar);
			}elseif($this->registeredNum($hnum, "name") != $fullname && $this->registeredNum($hnum, "id") == $id){
				//if the patient name changes but maintains hospital number
				$this->result = $this->patientChanges($id, $hnum, $surname, $fname, $oname, $age, $gender, $occupation, $registrar);
			}else{
				$this->result = $this->message_codes['Hospital']; //The hospital number you entered is registered to a different patient
			}

			return $this->result;
		}

	    	function updatePart($id, $part, $part_value){
			$query = $this->con->prepare("UPDATE $this->db_table SET $part = ? WHERE id = ?");
			$query->bind_param('ss', $part_value, $id);
			if($query->execute()){
				$this->alert("SC");
			}else{
				$this->alert("ER");
			}
		}

	 	function registeredNum($number, $id_or_name){
	 		$column = "hospital_num";
	 				 
	 		$results = $this->con->prepare("SELECT id, surname, first_name, other_name FROM $this->db_p_table WHERE $column = ? ");
			$return_val =  "";

			$results->bind_param("s", $number); 
			if($results->execute()){
				$results->bind_result($id, $surname, $first_name, $oname);
			
				while($results->fetch()){ 
				    switch($id_or_name){
						case 'name':
				    		$return_val =  $surname." ".$first_name." ".$oname; 
				    		break;
				    	case 'id':
				    		$return_val =  $id;
				    		break;
				    }
				}
			}

			return $return_val;
	 	}

	 	function toUpperCase($foo){
	 		foreach($foo as &$foo_bar) {//&foo_bar is the variable being changed by reference
		        $foo_bar = strtoupper($foo_bar);
		    }
		}
		 
		function reportNumExists($report_type, $report_num){
			$queery = $this->con->prepare("SELECT report_num FROM $this->db_r_table WHERE report_type = ? and report_num = ?"); 			
			
               $queery->bind_param("ss", $report_type, $report_num);

			$queery->execute();
			$queery->store_result();

			if($queery->num_rows > 0){
				return 1;
			}else{
				return 0;
			}
		}

	 	function getReportNum($report_type){
			$format = $this->getReportFormat($report_type);
			$results = "";
			$current_report_number = 0;

			$this->setTable($this->db_r_table);
			$where = "(SELECT MAX(id) FROM $this->db_table WHERE report_type = ?)";

			if($format == "IHC-"){
				$this->setTable($this->db_i_table);
				$where = "(SELECT MAX(id) FROM $this->db_table)";
			}

			//Select the report number of the last report saved of the same report type
			$results = $this->con->prepare("SELECT report_num FROM $this->db_table WHERE id = $where "); 

			if($format != "IHC-"){
				$results->bind_param("s", $report_type);			
			}

			$results->execute();			

			$results->bind_result($rnum); 
			
			if($results->fetch()){				
				$rnum_break_down = explode("-", $rnum); //breaking the report number into component parts

				$format = $rnum_break_down[0];	//report type
				$current_report_number = $rnum_break_down[1];	//the actual number
				$year = $rnum_break_down[2];	//the year

				if($this->getYear() > $year){ //setting the reports back to 1 every new year
					$current_report_number = 0;
				}
			}else{
				$format = trim($format, "-");
			}
			$current_report_number++;		    						
			
			return $format."-".$current_report_number."-".$this->getYear();	
		}

		function getSupportedDate($datetime){
			return date('Y-m-d',strtotime($datetime));
		}
	 	
	 	function getDate(){
	 		return date('Y-m-d', time());
	 	}

	 	function getTime(){
	 		return date('H:i', time()-7200);
	 	}

	 	function getYear(){
	 		return date('y', time());
		}
		 
		function alert($m_c, $custom = 0){ //must have bootstrap linked in page which this will show on and also custom CSS for modal_background
			$color =  $this->colors['inf'];

			if(!$custom){
				switch($m_c){
					case "SC":	
							$color = $this->colors['sc'];
							break;
					case "HN": case "IC":
							$color = $this->colors['wrn'];
							break;
					case "ER": case "LCK":
							$color = $this->colors['dng'] ;
							break;
					default: 
							$color = $this->colors['inf'];
							break;
				}

				$m_c = $this->attempts_left." ".$this->messages[$m_c];	
			}
			echo "<div class='modal_background' onclick='this.style.display=\"none\";'>
					<div class='alert $color'>
						<span class='closebtn' onclick='this.parentElement.style.display=\"none\";'>&times;</span>
						<h4>$m_c</h4>
					</div>
				</div>";				
		   }
		   
		   function switchVisibility(){ //hides a page which requires a request before it can be viewed, and displays a link returning to the home page
               $this->hyde = "hidden";                
                  
               echo "<div align='center'>
                    <h1>
                         <a href='index.php'>Home</a>
                    </h1>
               </div>";
          }

		function getNumRows($table_name){
			$query = $this->con->prepare("SELECT * FROM $table_name");
			$query->execute();
			$query->store_result();
			return $query->num_rows;
		}
		
		function prepareStmt($statement){
			return $this->con->prepare($statement);
		}

		function splitPOSTonToken($post_val, $delimiter){ 
			//useful when multiple checkboxes are checked and their value 
			//is stored in the same column in DB with a specified seperator (delimiter)
			$store_into = "";
			if(is_array($post_val)){
				foreach($post_val as $checked){
					$store_into .=$checked.$delimiter;
				}
				//remove last character - delimiter
				$store_into = rtrim($store_into, $delimiter);//substr($store_into, 0, -1);
			}else{
				$store_into = $post_val;
			}
			
			return $store_into;
		}

		function nl2p($string, $line_breaks = true, $xml = true) {
			$string = str_replace(array('<p>', '</p>', '<br>', '<br />'), '', $string);

			// It is conceivable that people might still want single line-breaks
			// without breaking into a new paragraph.
			if ($line_breaks == true)
			return '<p>'.preg_replace(array("/([\n]{2,})/i", "/([^>])\n([^<])/i"), array("</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'), trim($string)).'</p>';
			else 
			return '<p>'.preg_replace(
			array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"),
			array("</p>\n<p>", "</p>\n<p>", '$1<br'.($xml == true ? ' /' : '').'>$2'),

			trim($string)).'</p>'; 
		}
	}
?>