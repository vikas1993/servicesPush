<?php
 
error_reporting(-1);
ini_set('display_errors', 'On');
 
header('Access-Control-Allow-Origin: *');
require_once '../include/db_handler.php';
require '.././libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
 
 // User logged out using api key;
$app->get('/user/logout', function() use ($app) {
    // check for required params
	
	//Change param here
    verifyRequiredParams(array( 'email','api_key'));
	
    // reading post params
	//Change param here inside get only ....!!!!
	$email = $app->request->get('email');
	$api_key = $app->request->get('api_key'); 
	$db = new DbHandler();
	$response = $db->logoutUser($email,$api_key);

    echoRespnse(200, $response);
});
 

// User login only checks verified email id here
$app->get('/user/login/email', function() use ($app) {
    // check for required params
	//change param here
    verifyRequiredParams(array( 'email','email_verified'));

    // reading post params
	//Change param here inside get only ....!!!!
	$email = $app->request->get('email');
	$email_verified = $app->request->get('email_verified');
	if($email_verified !='true'){
		$response["error"]=true;
		$response["message"]="Email is not verified Just log the user out from the application";		
	}else{

		validateEmail($email);
		$db = new DbHandler();
		$response = $db->loginUserWithMailId($email);
	}
 
    // echo json response
    echoRespnse(200, $response);
});

// User login only checks verified email id here
$app->get('/user/update/details', function() use ($app) {
    // check for required params
	 //change param here
    verifyRequiredParams(array('email','first_name','last_name','dob','phone','street_address','city_address','state_address','zip_address','client_ss'));

    // reading post params
	//Change param here inside get only ....!!!!
	$email = $app->request->get('email');
	$first_name = $app->request->get('first_name');
	$middle_name = $app->request->get('middle_name');
	$last_name = $app->request->get('last_name');
	$dob = $app->request->get('dob');
	$phone = $app->request->get('phone');
	$street_address = $app->request->get('street_address');
	$city_address = $app->request->get('city_address');
	$state_address = $app->request->get('state_address');
	$zip_address = $app->request->get('zip_address');	
	$client_ss = $app->request->get('client_ss');	
	$client_ss = md5( $client_ss);
	$db = new DbHandler();
    $response = $db->updateUserDetails($email,$first_name,$middle_name,$last_name,$dob,$phone,$street_address,$city_address,$state_address,$zip_address,$client_ss);
    echoRespnse(200, $response);
});

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}
 
function IsNullOrEmptyString($str) {
    return (!isset($str) || trim($str) === '');
}
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}
 
$app->run();
?>