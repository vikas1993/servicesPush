<?php
 header('Access-Control-Allow-Origin: *');
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Vikas Aggarwal
 */
class DbHandler {
	private $base_url = "the base url will be added here for profile pic or any other link";
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . '/db_connect.php';
        // opening db connection
		//dl('php_mysqli_mysqlnd.dll');
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
	//logout function to deactivate the user so that other user cannot use same api key to do the functionalities
	public function logoutUser($email,$api_key){
	 if($this->isApiExists($api_key)){	
		if($this->logoutFunction($email,$api_key)){
				$response["error"]=false;
				$response["message"]="Now you have looged out from application";
		}else{
				$response["error"]=false;
				$response["message"]="You are already logged out";
		}
	 }else{
				$response["error"]=true;
				$response["message"]="Invalid Api Key";
	 }
	 return $response;
	}
	// function to change login status to 0 -> indicate user is logged out
	
	private function logoutFunction($email,$api_key){
		$stmt = $this->conn->prepare("Update  rw_manage_api set login_status = 0 where email = ? AND api_key = ?");				
			$stmt->bind_param("ss",$email,$api_key);			
			$stmt->execute();			
			print_r( $stmt->error);
			$stmt->store_result();
			$result = $stmt->affected_rows;
			$stmt->close();
			return $result > 0;	
	}
	//update user details in this function 
	public function updateUserDetails($email,$first_name,$middle_name,$last_name,$dob,$phone,$street_address,$city_address,$state_address,$zip_address,$client_ss){
		if($this->isUserExists($email)){
			if($this->updateUserAllDetails($email,$first_name,$middle_name,$last_name,$dob,$phone,$street_address,$city_address,$state_address,$zip_address,$client_ss)){
				$response["error"]=false;
				$response["message"]="Now you can use other features of the applications";
			}else{
				$response["error"]=false;
				$response["message"]="Same Details Are already updated";
			}
		
		}else{
			$response["error"]=true;
			$response["message"]="No user exist in our database please use login api to register the user first here";
		}
		return $response;
	}
	//upadate all the  details of the user into the database here
	private function updateUserAllDetails($email,$first_name,$middle_name,$last_name,$dob,$phone,$street_address,$city_address,$state_address,$zip_address,$client_ss){
		$stmt = $this->conn->prepare("Update  rw_users set first_name = ?,middle_name = ?,last_name = ?,dob = ?,phone = ?,street_address = ?,city_address = ?,state_address = ?,zip_address = ?,client_ss = ?,auth_user = 1 where email = ?");				
			$stmt->bind_param("sssssssssss",$first_name,$middle_name,$last_name,$dob,$phone,$street_address,$city_address,$state_address,$zip_address,$client_ss,$email);			
			$stmt->execute();			
			print_r( $stmt->error);
			$stmt->store_result();
			$result = $stmt->affected_rows;
			$stmt->close();
			return $result > 0;	
	}
	//checks if user has filled the details or not
	private function userHasFilledAllTheDetails($email){
		$stmt = $this->conn->prepare('SELECT * from rw_users WHERE email = ? AND 
							(first_name != null or first_name != "") AND
							(last_name != null or last_name != "") AND
							(dob != null or dob !="") AND
							(phone != null or phone !="") AND
							(street_address != null or street_address != "") AND
							(city_address != null or city_address !="") AND
							(state_address !=null or state_address !="") AND
							(zip_address !=null or zip_address !="") AND
							(client_ss !=null or client_ss !="")');       
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
	}
	//checks weather the user has filled all his details or not 
	public function loginUserWithMailId($email){
		if($this->isUserExists($email)){
			if($this->userHasFilledAllTheDetails($email)){
				$random_key = $this->genrateRandomApiKey();
				if($this->isUserExistsInApi($email)){
						
						if($this->updateApiOfUser($email,$random_key)){
							$response["error"] = false;
							$response["api_key"] = $random_key;
							$response["user"] = $this->getUserByEmail($email);
							
						}else{
							$response["error"] = true;
							$response["message"] = "Api not updated please contact developer";
						}
					}else{
						if($this->insertApiOfUser($email,$random_key)){
							$response["error"] = false;
							$response["api_key"] = $random_key;
							$response["user"] = $this->getUserByEmail($email);						
						}else{
							$response["error"] = true;
							$response["message"] = "Api not inserted please contact developer";
						}
					}
				
			}else{
				$response["error"] = true;
				$response["message"] = "User Has Not filled all his details please fill the details first then we will procedd further";
			}
		}else{
			$response = $this->registerUserByEmail($email);					
		}
		
		return $response;
	}
	//Used for authenticating user who has not used social login attempts  -->   not using right now
	public function authUser($user_auth_key){
		//echo $this->base_url;
		$response = array();
		if($this->isApiExistsInRegister($user_auth_key)){
					
					if($this->updateAuthUser($user_auth_key)){
						$response["error"] = false;
						$response["message"] = "Now User is authenticated here";
					}else{
						$response["error"] = true;
						$response["message"] = "Some Problem while authenticating user please check your mail";
					}
		}else{
					$response["error"] = true;
					$response["message"] = "In Valid Api";
		}
		return $response;
	}
	// Not In Use ........
	private function updateAuthUser($user_auth_key){
		 $stmt = $this->conn->prepare("Update  rw_users SET auth_user = 1 WHERE auth_api_key = ?");       
        $stmt->bind_param("s", $user_auth_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_rows > 0;
	}
    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
		 $stmt = $this->conn->prepare("SELECT email from rw_users WHERE email = ?");       
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
	 /**
     * Checking for duplicate user by email address --> Not Using 
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserAuthenticated($email) {
		 $stmt = $this->conn->prepare("SELECT email from rw_users WHERE email = ? and auth_user = 1");       
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
	//
	/**
     * checks wether api is already there in db or not 
     * @param String $email email to check in db
     * @return boolean
     */
	 private function isUserExistsInApi($email) {
		 $stmt = $this->conn->prepare("SELECT email from rw_manage_api WHERE email = ? and login_status = 0");       
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
	//For generating the random api key for register  --> Not In Use
	private function genrateRandomApiKeyForRegister(){
		$api_key = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
		if(!$this->isApiExistsInRegister($api_key)){
		return $api_key;
		}else{
			return $this->genrateRandomApiKey();
		}
	}
	//register user by email only and checks if user already exist
	 public function registerUserByEmail($email) {
		$response = array();
		
			if(!$this->isUserExists($email)){			
				$stmt = $this->conn->prepare("Insert into  rw_users (email) values(?)");				
				$stmt->bind_param("s", $email);			
				$stmt->execute();			
				print_r( $stmt->error);
				$stmt->store_result();
				$result = $stmt->affected_rows;
				$stmt->close();
				if($result > 0){
					$response["error"] = true;
					$response["message"] = "User is registeres in our database now use update api to fill other details";
				}else{
					$response["error"] = true;
					$response["message"] = "There is some problem please check details you have entered";
				};			
			}else{
				$response["error"] = true;
				$response["message"] = "This Email already Exists";
			}
		
			return $response;
    }
	 /**
     * registring the user into our database  --> Old Function Not in Use !!!!!!
     * @param String $email email to check in db
     * @return boolean
     */
    public function registerUser($email,$first_name,$middle_name,$last_name,$isSocialLogin,$password) {
		$response = array();
		if($isSocialLogin == "true"){
			if(!$this->isUserExists($email)){			
				$stmt = $this->conn->prepare("Insert into  rw_users (email,first_name,middle_name,last_name,is_social_login) values(?,?,?,?,?)");				
				$stmt->bind_param("sssss", $email,$first_name,$middle_name,$last_name,$isSocialLogin);			
				$stmt->execute();			
				print_r( $stmt->error);
				$stmt->store_result();
				$result = $stmt->affected_rows;
				$stmt->close();
				if($result > 0){
					$response["error"] = false;
					$response["message"] = "User is registeres in our database";
				}else{
					$response["error"] = true;
					$response["message"] = "There is some problem please check details you have entered";
				};			
			}else{
				$response["error"] = true;
				$response["message"] = "This Email already Exists";
			}
		}else{
			$auth_api_key = $this->genrateRandomApiKeyForRegister();
			if(!$this->isUserExists($email)){	
				$stmt = $this->conn->prepare("Insert into  rw_users (email,auth_user,auth_api_key,first_name,middle_name,last_name,is_social_login,password) values(?,0,?,?,?,?,?,?)");				
				$stmt->bind_param("sssssss", $email,$auth_api_key,$first_name,$middle_name,$last_name,$isSocialLogin,$password);			
				$stmt->execute();			
				print_r( $stmt->error);
				$stmt->store_result();
				$result = $stmt->affected_rows;
				$stmt->close();
				if($result > 0){
					$response["error"] = false;
					$response["message"] = "User is registeres in our database and send link to their mail";
					$this->sendMailToThisMailId($email,"Verify Mail Id","Use this link for verify your mail id /n /n Please Click here to verify your mail id \n".$this->base_url."/verify/user?user_auth_key=".$auth_api_key);
				}else{
					$response["error"] = true;
					$response["message"] = "There is some problem please check details you have entered";
				};	
			}else{
				if($this->isUserAuthenticated($email)){
					$response["error"] = false;
					$response["message"] = "This Email is already registered with us";
				}else{
					$response["error"] = true;
					$response["message"] = "User is Not Authenticated";
					//$this->sendMailToThisMailId($email,"Verify Mail Id","Use this link for verify your mail id /n /n link will be provided soon");
				}
			}	
		}
			return $response;
    }
	// for sending the mail to the user is here ;
	private function sendMailToThisMailId($to,$subject,$txt){
			$headers = "From: vicky@example.com" . "\r\n" .
			"CC: somebodyelse@example.com";
			mail($to,$subject,$txt,$headers);
	}
	//
	// For inserting the api of the user
	 public function insertApiOfUser($email,$api_key) {				
			$stmt = $this->conn->prepare("Insert into  rw_manage_api (api_key,email,login_status) values(?,?,1)");				
			$stmt->bind_param("ss", $api_key,$email);			
			$stmt->execute();			
			print_r( $stmt->error);
			$stmt->store_result();
			$result = $stmt->affected_rows;
			$stmt->close();
			return $result > 0;			
			 
    }
	// For updating the api of the user when the user gets logged out
	 public function updateApiOfUser($email,$api_key) {		
		if(!$this->isEmptyApiAvaialable($email)){
			$stmt = $this->conn->prepare("Update  rw_manage_api set api_key = ?,login_status = 1 where email = ? limit 1");				
			$stmt->bind_param("ss", $api_key,$email);			
			$stmt->execute();			
			print_r( $stmt->error);
			$stmt->store_result();
			$result = $stmt->affected_rows;
			$stmt->close();
			return $result > 0;	
		}else{
			$stmt = $this->conn->prepare("Update rw_manage_api set api_key = ?,login_status = 1 where email = ? and login_status = 0 limit 1");				
			$stmt->bind_param("ss", $api_key,$email);			
			$stmt->execute();			
			print_r( $stmt->error);
			$stmt->store_result();
			$result = $stmt->affected_rows;
			$stmt->close();
			return $result > 0;	
		}			
    }
	// Check for empty slot for api 
	 public function isEmptyApiAvaialable($email) {				
			$stmt = $this->conn->prepare("Select * from   rw_manage_api where email = ?");				
			$stmt->bind_param("s", $email);			
			$stmt->execute();			
			print_r( $stmt->error);
			$stmt->store_result();
			$result = $stmt->affected_rows;
			$stmt->close();
			return $result > 0;				 
    }
	//new functions
    /**
     * Fetching user details by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {		
        $stmt = $this->conn->prepare("SELECT first_name, middle_name, last_name,dob,phone,street_address,city_address,state_address,zip_address,client_ss,rw_users.email, api_key from rw_users,rw_manage_api where rw_users.email= rw_manage_api.email and rw_users.email = ? ");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($first_name,$middle_name,$last_name,$dob,$phone,$street_address,$city_address,$state_address,$zip_address,$client_ss,$email,$api_key);
            $stmt->fetch();
            $user = array();           
            $user["first_name"] = $first_name;
            $user["middle_name"] = $middle_name;
            $user["last_name"] = $last_name;
			
			$user["dob"] = $dob;
			$user["phone"] = $phone;
			$user["street_address"] = $street_address;
			$user["city_address"] = $city_address;
			$user["state_address"] = $state_address;
			$user["zip_address"] = $zip_address;
			$user["client_ss"] = $client_ss;			
			$user["email"] = $email;
			
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
	//Login api for user  --> Not In Use !!!!!
	
	public function loginUser($email) {
		//echo "hi";
		$response = array();
			$random_key = $this->genrateRandomApiKey();
			$login = $this->isUserSocialLogin($email);
			if($login){
				if($this->isUserExistsInApi($email)){
					
					if($this->updateApiOfUser($email,$random_key)){
						$response["error"] = false;
						$response["api_key"] = $random_key;
						$response["user"] = $this->getUserByEmail($email);
						
					}else{
						$response["error"] = true;
						$response["message"] = "Api not updated please contact developer";
					}
				}else{
					if($this->insertApiOfUser($email,$random_key)){
						$response["error"] = false;
						$response["api_key"] = $random_key;
						$response["user"] = $this->getUserByEmail($email);						
					}else{
						$response["error"] = true;
						$response["message"] = "Api not inserted please contact developer";
					}
				}
			}else{
						$response["error"] = true;
						$response["message"] = "User is not listed in our database or it is not authenticated";				
			}
			 return $response;
				 
    }
	// check if user has login through social network sites i.e facebook,twitter, google etc; --> Not in use
	private function isUserSocialLogin($email){
		$stmt = $this->conn->prepare("SELECT * from rw_users WHERE email = ? and ( is_social_login = 'true'   OR auth_user = 1)");       
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
		//print_r($stmt->error);
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
	}
	//For generating the random api key 
	private function genrateRandomApiKey(){
		$api_key = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
		//checks if api key is already in database or not
		if(!$this->isApiExists($api_key)){
		return $api_key;
		}else{
			return $this->genrateRandomApiKey();
		}
	}
	 /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isApiExists($api_key) {
		 $stmt = $this->conn->prepare("SELECT api_key from rw_manage_api WHERE api_key = ?");       
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
		//print_r($stmt->error);
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
	 /**
     * Checking for duplicate user by email address  --> Not in use 
     * @param String $email email to check in db
     * @return boolean
     */
    private function isApiExistsInRegister($api_key) {
		 $stmt = $this->conn->prepare("SELECT auth_api_key from rw_users WHERE auth_api_key = ?");       
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
		//print_r($stmt->error);
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
 
}
 
?>