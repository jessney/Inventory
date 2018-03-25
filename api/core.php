<?php
//include 'db.php';

//MAIN CREVA API CLASS
class invCoreAPI{
	
	var $skey 	= "SuPerEncKey2010"; // you can change it
	
	public function generateRandomString($length = 43) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
	}
	
	public function generateToken($length = 43) {
		return bin2hex(openssl_random_pseudo_bytes($length));
	}
	
    public  function safe_b64encode($string) {
	
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

	public function safe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
	
    public  function encode($value){ 
		
	    if(!$value){return false;}
        $text = $value;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->skey, $text, MCRYPT_MODE_ECB, $iv);
        return trim($this->safe_b64encode($crypttext)); 
    }
    
    public function decode($value){
		
        if(!$value){return false;}
        $crypttext = $this->safe_b64decode($value); 
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->skey, $crypttext, MCRYPT_MODE_ECB, $iv);
        return trim($decrypttext);
    }
	
	public function server_public_key($pk)
	{
		return $this->encode($pk);
	}
	
	public function server_private_key($pk)
	{
		return $this->encode($pk);
	}

	public function public_key()
	{
		return $this->generateRandomString();
	}
	
	public function private_key()
	{
		return $this->generateRandomString();
	}	

	public function decode_server_public_key($key)
	{
		return $this->decode($key);
	}
	
	public function decode_server_private_key($key)
	{
		return $this->decode($key);
	}
		
	//filter keys both for pub keys ang priv keys to limit similar generated keys
	public function filter_keys($pubKey,$privKey)
	{
		$en_pubKey = $this->encode($pubKey);
		$en_privKey = $this->encode($privKey);
		//$en_pubKey = "qU3M7gxAR1nISVw-Ct-LDhwnjvS935Lj1uO4jWYtdr8k1ir3yDLjUNU06ADLzQsN4ehg3AQHbXsoYOVlzwGf7w";//$this->encode($pubKey);
		//$en_privKey = "jYqdlH7RFgPVS7Jb-KPIIgGHWKU8de8pqKEosYBi5Wy4xHSCetys5nI9iXAwHC3oJ1gvXwaPxW0oaladza8tGA";//$this->encode($privKey);
		try {
		$db = getDB();
		$sql = "SELECT count(*) as `key_count` FROM `keys` WHERE private_key='$en_privKey'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$count_privKeys = $stmt->fetchAll();
		$db = null;
		} catch(PDOException $e) {		
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
		
		try {
		$db = getDB();
		$sql = "SELECT count(*) as `key_count` FROM `keys` WHERE public_key='$en_pubKey'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$count_pubKeys = $stmt->fetchAll();
		$db = null;
		} catch(PDOException $e) {		
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}

		if(empty($count_pubKeys[0]['key_count']) && empty($count_privKeys[0]['key_count']))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function loginUser($email,$password)
	{
		$email = $this->encode($email);
		$pass = hash("sha512",$this->encode($password));
		
		try {
		$db = getDB();
		$sql = "SELECT count(*) as `userCount` FROM `member` WHERE `email`='$email' AND `password`='$pass'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$count_user = $stmt->fetchAll();
		$db = null;
		//print_r($count_user[0]['userCount']);
		if(empty($count_user[0]['userCount']))
		{
			return false;
		}
		else
		{
			return true;
		}
		
		} catch(PDOException $e) {		
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
		

	}
	
	public function token()
	{
		return $this->encode(openssl_random_pseudo_bytes(64)); 
	}

	public function auth_request_token($authToken)
	{
		try {
		$db = getDB();
		$sql = "SELECT count(*) as `token_count` FROM `request_token` WHERE token='$authToken'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$token_count = $stmt->fetchAll();
		$db = null;
		} catch(PDOException $e) {		
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
		}
		
		if(empty($token_count[0]['token_count']))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
}

$creva = new invCoreAPI();
//echo $creva->request_token();


/**
echo $g1 = ($creva->public_key());
echo "<br />";
echo $g2 = ($creva->private_key());
echo "<br />";
echo "<br />";
echo strlen($creva->public_key());
echo "<br />";
echo strlen($creva->private_key());
echo "<br />";
echo "<br />";
echo $t1 = ($creva->server_public_key($g1));
echo "<br />";
echo $t2 = ($creva->server_private_key($g2));
echo "<br />";
echo "<br />";
echo ($creva->decode_server_public_key($t1));
echo "<br />";
echo ($creva->decode_server_private_key($t2));
echo "<br />";
echo "<br />";
echo ($creva->generateToken());
**/
?>