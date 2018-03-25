<?php
include 'db.php';
require 'core.php';
require '../vendor/autoload.php';

$app = new \Slim\App;

$app->get('/v1/create/user','createUser');
/**
$app->get('/v1/token/request','requestToken');
$app->get('/v1/token/access','accessToken');
$app->get('/v1/login','login');
$app->get('users','getUsers');
$app->get('/users/:uid','getUsersId');
$app->get('/updates','getUserUpdates');
$app->post('/updates', 'insertUpdate');
$app->delete('/updates/delete/:update_id','deleteUpdate');
$app->get('/users/search/:query','getUserSearch');
**/

// Define app routes
$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->write("Hello " . $args['name']);
});

$app->run();


function createUser() {

	global $app;
	

	$date = date("Y-m-d H:i:s");
	$request = \Slim\Slim::getInstance()->request();
	$update = json_decode($request->getBody());
	$sql = 'INSERT INTO `member` (memType, memID, email, password, pin, recommend, reg_date, auth_email,
								auth_phone, m_name, m_phone, m_birthday, m_gender, crvc_v_addr, last_login_ip, last_login_time, toDate, 
								mem_status, isNormal) 
						VALUES ("","",:email,:password,"","",:date,"","",:name,:phone,:birthday,:gender,"","","","","","")';
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$email = $app->request()->params('email');
		$password = hash("sha512",($app->request()->params('password')));
		$name = $app->request()->params('name');
		$phone = $app->request()->params('phone');
		$birthday = $app->request()->params('birthday');
		$gender = $app->request()->params('gender');
		
		$stmt->bindParam("email", $email);
		$stmt->bindParam("password", $password);
		$stmt->bindParam("name", $name);
		$stmt->bindParam("birthday",$birthday);
		$stmt->bindParam("phone", $phone);
		$stmt->bindParam("gender", $gender);
		$stmt->bindParam("date", $date);
		//$ip=$_SERVER['REMOTE_ADDR'];
		//$stmt->bindParam("ip", $ip);
		$stmt->execute();
		$mem_id = $db->lastInsertId();
		$db = null;
		createUserKey($mem_id);
		
	} catch(PDOException $e) {
		//error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function createUserKey($mem_id) {
	
	global $creva;
	
	//$request = \Slim\Slim::getInstance()->request();
	//$update = json_decode($request->getBody());
	$sql = 'INSERT INTO `keys` (`mem_idx`,`private_key`,`public_key`) VALUES(:mem_id,:private_key,:public_key)';
						
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		
		$privKey = $creva->private_key();
		$pubKey = $creva->public_key();
		
		$filter = $creva->filter_keys($pubKey,$privKey);
		$status_key = true;
		
		#filter if key is unique 
		while($status_key)
		{
			if($filter)
			{
				$public_key = $creva->server_public_key($pubKey);
				$private_key = $creva->server_private_key($privKey);
				$status_key = false;
			}
			else
			{
				echo $public_key = $creva->server_public_key($pubKey);
				echo $private_key = $creva->server_private_key($privKey);
				$status_key = true;
			}
			
		}
		
		$stmt->bindParam("public_key", $public_key);
		$stmt->bindParam("private_key", $private_key);
		$stmt->bindParam("mem_id", $mem_id);
		
		$stmt->execute();
		$db = null;
		echo '{"response":{"code":200,"text":"OK","description":"Success!"}}';
	} catch(PDOException $e) {
		//error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}						
}


function requestToken() {
	
	global $creva,$app;
	
	$request = \Slim\Slim::getInstance()->request();
	$date = date("Y-m-d H:i:s",strtotime("now"));
	$return_url = $app->request()->params('return_url');
	$token = $creva->token();
	$sql = 'INSERT INTO `request_token` (`token`,`date`) VALUES(:token,:date)';
	
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$return_url = $app->request()->params('return_url');
		$stmt->bindParam("token", $token);
		$stmt->bindParam("date",$date);

		$stmt->execute();
		$db = null;

		echo '{"response":{"code":200,"text":"OK","description":"Success!","requestToken":"'.$token.'","return_url":"'.$return_url.'"}}';
		
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
	
}

function accessToken($rt) {
	
	global $creva,$app;
	
	$token = $creva->token();
	$date = date("Y-m-d H:i:s",strtotime("now"));
	$expire = date("Y-m-d H:i:s",(strtotime("now") + 2700));
	$token_expiration = $expire;
	$mem_id = 11;
	$request = \Slim\Slim::getInstance()->request();
	
	$sql = 'INSERT INTO `access_token` (`mem_idx`,`token`,`token_expiration`,`date`) VALUES(:mem_id,:token,:token_expiration,:date)';
	
	
	// major problem $creva->auth_request_token($rt)
	if($creva->auth_request_token($rt)){
		try {
			$db = getDB();
			$stmt = $db->prepare($sql);  
			$return_url = $app->request()->params('return_url');
			$stmt->bindParam("mem_id", $mem_id);
			$stmt->bindParam("token", $token);
			$stmt->bindParam("token_expiration", $token_expiration);
			$stmt->bindParam("date",$date);

			$stmt->execute();
			$db = null;
			//$e = apache_request_headers();
			//echo json_encode($_SERVER,true);
			//echo '{"response":{"code":200,"text":"OK","description":"Success!","token":"'.$token.'","return_url":"'.$return_url.'"}}';
			return $token;
			
			
		} catch(PDOException $e) {
			echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
	}
}

function login() {
	
	global $creva,$app;

	$request = \Slim\Slim::getInstance()->request();
	$return_url = $app->request()->params('return_url');
	$email = $app->request()->params('email');
	$password = $app->request()->params('psswd');

	if($creva->loginUser($email,$password))
	{
		$token = accessToken($_SERVER['HTTP_Authorization']);
		echo '{"response":{"code":200,"text":"OK","description":"Success!","accessToken":"'.$token.'","return_url":"'.$return_url.'"}}';
	}
	else
	{
		//echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		echo "waaa";
	}
}











function getUsers() {
	$sql = "SELECT user_id,username,name,profile_pic FROM users ORDER BY user_id";
	try {
		$db = getDB();
		$stmt = $db->query($sql);  
		$users = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"users": ' . json_encode($users) . '}';
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function getUsersId($uid) {
	$sql = "SELECT user_id,username,name,profile_pic FROM users WHERE user_id=$uid ORDER BY user_id";
	try {
		$db = getDB();
		$stmt = $db->query($sql);
		$stmt->bindParam("uid", $uid);
		$stmt->execute();
		$users = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"users": ' . json_encode($users) . '}';
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function getUserUpdates() {
	$sql = "SELECT A.user_id, A.username, A.name, A.profile_pic, B.update_id, B.user_update, B.created FROM users A, updates B WHERE A.user_id=B.user_id_fk  ORDER BY B.update_id DESC";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();		
		$updates = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"updates": ' . json_encode($updates) . '}';
		
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function getUserUpdate($update_id) {
	$sql = "SELECT A.user_id, A.username, A.name, A.profile_pic, B.update_id, B.user_update, B.created FROM users A, updates B WHERE A.user_id=B.user_id_fk AND B.update_id=:update_id";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);
        $stmt->bindParam("update_id", $update_id);		
		$stmt->execute();		
		$updates = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"updates": ' . json_encode($updates) . '}';
		
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function insertUpdate() {
	$request = \Slim\Slim::getInstance()->request();
	$update = json_decode($request->getBody());
	$sql = "INSERT INTO updates (user_update, user_id_fk, created, ip) VALUES (:user_update, :user_id, :created, :ip)";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("user_update", $update->user_update);
		$stmt->bindParam("user_id", $update->user_id);
		$time=time();
		$stmt->bindParam("created", $time);
		$ip=$_SERVER['REMOTE_ADDR'];
		$stmt->bindParam("ip", $ip);
		$stmt->execute();
		$update->id = $db->lastInsertId();
		$db = null;
		$update_id= $update->id;
		getUserUpdate($update_id);
	} catch(PDOException $e) {
		//error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function deleteUpdate($update_id) {
   
	$sql = "DELETE FROM updates WHERE update_id=:update_id";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("update_id", $update_id);
		$stmt->execute();
		$db = null;
		echo true;
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
	
}

function getUserSearch($query) {
	$sql = "SELECT user_id,username,name,profile_pic FROM users WHERE UPPER(name) LIKE :query ORDER BY user_id";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);
		$query = "%".$query."%";  
		$stmt->bindParam("query", $query);
		$stmt->execute();
		$users = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"users": ' . json_encode($users) . '}';
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}
?>