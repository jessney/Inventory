<?php
session_start();

//$_SESSION['r'] = false;
//$_SESSION['token'] = "";

//if there is a request token go directly to signup page
if($_SESSION['r'])
{
	//ACCESS TOKEN	
	$authToken = $_SESSION['request_token'];
	$action = "http://localhost/api/v1/login";
	$url = $_POST['return_url'];
	// if empty session token go directly to signup page
	if(empty($_SESSION['token'])):
		if($_REQUEST['s']!=1):
	?>
	
		<form class="login-form" action="<?php echo $url; ?>" method="POST" >
		<p>Email</p>
		<p><input type="text" name="email" ></p>
		<p>Password</p>
		<p><input type="password" name="password" ></p>
		<p><input type="submit" value="submit" ></p>
		<input type="hidden" name="rqst" value="<?php echo $authToken; ?>" />
		<input type="hidden" name="s" value="1" />
		</form>
	
	<?php
		else:
		
		$email = $_REQUEST['email'];
		$pass = $_REQUEST['password'];
		$authToken = $_REQUEST['rqst'];
		
		$ch = curl_init("http://localhost/api/v1/login?email=$email&psswd=$pass");
		curl_setopt_array($ch, array(
			CURLOPT_POST => FALSE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => array(
				'Authorization: '.$authToken,
				'Content-Type: application/json'
			)
		));
		//echo "http://localhost/api/v1/login?email=$email&psswd=$pass";
		$response = curl_exec($ch);
		
		$responseData = json_decode($response, TRUE);
		print_r($responseData);
		endif;
	else:	
	
	$ch = curl_init("http://localhost/api/v1/token/access?return_url=www.google.com");
	curl_setopt_array($ch, array(
		CURLOPT_POST => FALSE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HTTPHEADER => array(
			'Authorization: '.$authToken,
			'Content-Type: application/json'
		)
	));

	// Send the request
	$response = curl_exec($ch);

	// Check for errors
	if($response === FALSE){
		die(curl_error($ch));
	}

	// Decode the response
	$responseData = json_decode($response, TRUE);

	// Print the date from the response
	print_r($responseData);

	$_SESSION['token'] = $responseData['response']['token'];
	endif;
}
else
{
	//REQUEST TOKEN		
	$ch = curl_init("http://localhost/api/v1/token/request?return_url="."http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	curl_setopt_array($ch, array(
		CURLOPT_POST => FALSE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json'
		)
	));
	$response = curl_exec($ch);

	// Check for errors
	if($response === FALSE){
		die(curl_error($ch));
	}
	
	// Decode the response
	$responseRequest = json_decode($response, TRUE);

	//print_r($responseRequest['response']);

	$_SESSION['request_token'] = $responseRequest['response']['requestToken'];

	if(!empty($_SESSION['request_token']))
	{
		$_SESSION['r'] = true;
		echo "<script> location.reload(); </script>";
	}
}
//print_r($_SESSION['r'] );
//session_destroy();

?>