<?php
// DEfunct 
/*use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
$app = new \Slim\App;
$app->get('/api/user/{publicKey}', function(Request $req, Response $resp){
	$publicKey = $req->getAttribute('publicKey');
	$sql = "select users.username as username, users.email as email, users.fullname as fullname, users.imageDir as imageDir, users.phone as phone, wallets.GCinWallet as GCinWallet, wallets.BMC as BMC from users Left join wallets on wallets.userID = users.id where users.publicKey='".$publicKey."'";
	try{
		$db = new db();
		$db = $db->connect();
		$stmt = $db->query($sql);
		$data = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		$data = json_encode($data);
		echo '{"error":{"message":"","status":""},"user":"'.$data.'"}';

	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "status":"fatal"}}';
	}
});
$app->post('/api/user/create', function(Request $req, Response $resp){
	$username = $req->getParam('username');
	$fullname = $req->getParam('fullname');
	$email = $req->getParam('email');
	$pass = $req->getParam('password');
	$imageDir = $req->getParam('profileImage');
	$phone = $req->getParam('phone');
	$key = ' FitSKchgoHOOKing666';
	$string = $pass;
	$iv = mcrypt_create_iv(
	    mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC),
	    MCRYPT_DEV_URANDOM
	);

		$encryptedPassword = base64_encode(
		    $iv .
		    mcrypt_encrypt(
		        MCRYPT_RIJNDAEL_128,
		        hash('sha256', $key, true),
		        $string,
		        MCRYPT_MODE_CBC,
		        $iv
		    )
	);
	$mcrypto = new mcrypt();
	$publicKey = $mcrypto->mCryptThis(time());
	try{
		$dbn = new db();
		$dbn = $dbn->connect();
		$stmt = $dbn->prepare("INSERT INTO user (username,email,fullname, password,phone,publicKey,imageDir) VALUES (?,?,?,?,?,?,?)");
		$stmt->bindParam(1, $username);
		$stmt->bindParam(2, $email);
		$stmt->bindParam(3, $fullname);
		$stmt->bindParam(4, $encryptedPassword);
		$stmt->bindParam(5, $phone);
		$stmt->bindParam(6, $publicKey);
		$stmt->bindParam(7, $imageDir);
		$stmt->execute();
		$_SESSION['sk'] = $publicKey;
		$_SESSION['ji'] = $username;
		echo '{"error":{"message":"", "fatal":""},"success":{"message":"Admin created","code":"200"}}';
	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "fatal":"true"}}';
	}
});*/