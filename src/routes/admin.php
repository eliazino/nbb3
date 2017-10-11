<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;
//Create admin
$app->get('/api/admin/{publicKey}', function(Request $req, Response $resp){
	$publicKey = $req->getAttribute('publicKey');
	$sql = "select*from admin where publicKey='".$publicKey."'";
	try{
		$db = new db();
		$db = $db->connect();
		$stmt = $db->query($sql);
		$data = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		$data = json_encode($data);
		echo '{"error":{"message":"","status":""},"admin":"'.$data.'"}';

	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "status":"fatal"}}';
	}
});
$app->get('/api/admin/get/xchange', function(Request $req, Response $resp){
	$sql = "select*from exchangerate";
	try{
		$db = new db();
		$db = $db->connect();
		$stmt = $db->query($sql);
		$data = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		$data = json_encode($data);
		echo '{"error":{"message":"","status":""},"exchangeRate":"'.$data.'"}';
	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "status":"fatal"}}';
	}
});
$app->put('/api/admin/set/xchange', function(Request $req, Response $resp){
	$ngnBuyValue = $req->getParam('ngnBuyValue');
	$BMCBuyValue = $req->getParam('BMCBuyValue');
	$GCinWalletBuyValue = $req->getParam('GCinWalletBuyValue');
	$BTCBuyValue = $req->getParam('BTCBuyValue');
	$ngnSellValue = $req->getParam('ngnBuyValue');
	$BMCSellValue = $req->getParam('BMCSellValue');
	$GCinWalletSellValue = $req->getParam('GCinWalletSellValue');
	$BTCSellValue = $req->getParam('BTCSellValue');
	$admin = $req->getParam('username');
	$publicKey = $req->getParam('publicKey');
	try{
		$dbn = new db();
		$sql = "select count(*) from admin where username='".$admin."' and publicKey='".$publicKey."'";
		$dbn = $dbn->connect();
		$qr = $dbn->query($sql);
		if ($qr->fetchColumn() > 0) {
			$nsql = "UPDATE exchangerate t1 JOIN exchangerate t2 JOIN exchangerate t3 JOIN exchangerate t4
    				ON t1.label = 'NGN' AND t2.label = 'BMC' AND t3.label = 'BTC' AND t4.label = 'GCinWallet'
				   SET t1.exchangeSellValue = '".$ngnSellValue."', t2.exchangeSellValue = '".$BMCSellValue."',
				       t3.exchangeSellValue = '".$BTCSellValue."', t4.exchangeSellValue = '".$GCinWalletSellValue."',
				       t1.exchangeBuyValue = '".$ngnBuyValue."', t2.exchangeBuyValue = '".$BMCBuyValue."',
				       t3.exchangeBuyValue = '".$BTCBuyValue."',   t4.exchangeBuyValue = '".$GCinWalletBuyValue."'";
			$stmt = $dbn->prepare($nsql);
			$stmt->execute();
			echo '{"error":{"message":"", "fatal":""}, "success":{"message":"Update was successful","code":"200"}}';
		}else{
			echo '{"error":{"message":"There seems to be previledge issue. Login again", "fatal":"true"}}';
		}
	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "fatal":"true"}}';
	}
	//echo "data";
});
$app->post('/api/admin/create', function(Request $req, Response $resp){
	$username = $req->getParam('username');
	$fullname = $req->getParam('fullname');
	$email = $req->getParam('email');
	$pass = $req->getParam('password');
	$imageDir = $req->getParam('profileImage');
	$phone = $req->getParam('phone');
	$key = ' FitSKchgoHOOKing666';
	$string = $key.'34iIlm'.$pass.'io9m-';
	$encryptedPassword = hash('sha256', $string);
	$mcrypto = new mcrypt();
	$publicKey = $mcrypto->mCryptThis(time());
	try{
		$dbn = new db();
		$dbn = $dbn->connect();
		$stmt = $dbn->prepare("INSERT INTO admin (username,email,fullname, password,phone,publicKey,imageDir) VALUES (?,?,?,?,?,?,?)");
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
});
$app->put('/api/admin/edit/profile', function(Request $req, Response $resp){
	$username = $req->getParam('username');
	$publicKey = $req->getParam('publicKey');
	$fullname = $req->getParam('fullname');
	$email = $req->getParam('email');
	$pass = $req->getParam('password');
	$imageDir = $req->getParam('imageDir');
	$phone = $req->getParam('phone');
	if(isset($username) and isset($publicKey)){
		try{
			$dbn = new db();	
			$sql = "select count(*) from admin where username='".$username."' and publicKey='".$publicKey."'";
			$dbn = $dbn->connect();
			$qr = $dbn->query($sql);
			if ($qr->fetchColumn() > 0) {
				$nsql = "update admin set fullname = '".$fullname."', email = '".$email."', phone = '".$phone."',
				imageDir = '".$imageDir."' where publicKey='".$publicKey."'";
				$stmt = $dbn->prepare($nsql);
			    $stmt->execute();
			    //$stmt->rowCount()
				echo '{"error":{"message":"", "fatal":""}, "success":{"message":"Update was successful","code":"200"}}';
			}else{
				echo '{"error":{"message":"There seems to be previledge issue Login again", "fatal":"true"}}';
			}
			$qr = null;
		}catch(PDOException $e){
			echo '{"error":{"message":"'.$e->getMessage().'", "fatal":"true"}}';
		}
	}else{
		echo '{"error":{"message":"username and publicKey is required", "fatal":"true"}}';
	}
});
$app->post('/api/admin/login', function(Request $req, Response $resp){
	$username = $req->getParam('username');
	$password = $req->getParam('password');
	$key = ' FitSKchgoHOOKing666';
	$string = $key.'34iIlm'.$password.'io9m-';
	$encryptedPassword = hash('sha256', $string);
	$mcrypto = new mcrypt();
	$publicKey = $mcrypto->mCryptThis(time());
	if(isset($username) and isset($password)){
		try{
			$dbn = new db();	
			$sql = "select count(*) from admin where username='".$username."' and password='".$encryptedPassword."'";
			$dbn = $dbn->connect();
			$qr = $dbn->query($sql);
			if ($qr->fetchColumn() > 0) {
				$sql = "update admin set publicKey = '".$publicKey."' where username = '".$username."' and password = '".$password."'";
				$stmt = $dbn->prepare($sql);
			    $stmt->execute();
			    $_SESSION['sk'] = $publicKey;
				$_SESSION['ji'] = $username;
				echo '{"error":{"message":"", "fatal":"true"}, "success":{"message":"Login successful", "status":"200"}}';
			}else{
				echo '{"error":{"message":"Login credentials match not found", "fatal":"true"}}';
			}
		}catch(PDOException $e){
			echo '{"error":{"message":"'.$e->getMessage().'", "fatal":"true"}}';
		}
	}else{
		echo '{"error":{"message":"Login credentials not found", "fatal":"true"}}';
	}
});
$app->get('/api/admin/test/{data}', function(Request $req, Response $res){
	$pass = $req->getAttribute('data');
	//$key = ' FitSKchgoHOOKing666';
	$key = ' FitSKchgoHOOKing666';
	$string = $key.'34iIlm'.$pass.'io9m-';
	$encryptedPassword = hash('sha256', $string);
	echo $encryptedPassword;
		//echo $encryptedPassword;
});



$app->get('/api/user/{publicKey}', function(Request $req, Response $resp){
	$publicKey = $req->getAttribute('publicKey');
	$sql = "select users.username as username, users.email as email, users.fullname as fullname, users.imageDir as imageDir, users.phone as phone, wallets.GCinWallet as GCinWallet, wallets.BMC as BMC, wallets.BTC as BTC, wallets.NGN as NGN from users Left join wallets on wallets.userID = users.id where users.publicKey='".$publicKey."'";
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
	$string = $key.'34iIlm'.$pass.'io9m-';
	$encryptedPassword = hash('sha256', $string);
	$mcrypto = new mcrypt();
	$publicKey = $mcrypto->mCryptThis(time());
	try{
		$dbn = new db();
		$dbn = $dbn->connect();
		$stmt = $dbn->prepare("INSERT INTO users (username,email,fullname, password,phone,publicKey,imageDir) VALUES (?,?,?,?,?,?,?)");
		$stmt->bindParam(1, $username);
		$stmt->bindParam(2, $email);
		$stmt->bindParam(3, $fullname);
		$stmt->bindParam(4, $encryptedPassword);
		$stmt->bindParam(5, $phone);
		$stmt->bindParam(6, $publicKey);
		$stmt->bindParam(7, $imageDir);
		$stmt->execute();
		$id = $dbn->lastInsertId();
        //$id = $result["id"]; 
		$stmt = $dbn->prepare("insert into wallets (userID) values (".$id.")");
		$stmt->execute();
		$_SESSION['sk'] = $publicKey;
		$_SESSION['ji'] = $username;
		echo '{"error":{"message":"", "fatal":""},"success":{"message":"user created","code":"200"}}';
	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "fatal":"true"}}';
	}
});
$app->put('/api/user/edit/profile', function(Request $req, Response $resp){
	$username = $req->getParam('username');
	$publicKey = $req->getParam('publicKey');
	$fullname = $req->getParam('fullname');
	$email = $req->getParam('email');
	$pass = $req->getParam('password');
	$imageDir = $req->getParam('imageDir');
	$phone = $req->getParam('phone');
	if(isset($username) and isset($publicKey)){
		try{
			$dbn = new db();	
			$sql = "select count(*) from users where username='".$username."' and publicKey='".$publicKey."'";
			$dbn = $dbn->connect();
			$qr = $dbn->query($sql);
			if ($qr->fetchColumn() > 0) {
				$nsql = "update users set fullname = '".$fullname."', email = '".$email."', phone = '".$phone."',
				imageDir = '".$imageDir."' where publicKey='".$publicKey."'";
				$stmt = $dbn->prepare($nsql);
			    $stmt->execute();
			    //$stmt->rowCount()
				echo '{"error":{"message":"", "fatal":""}, "success":{"message":"Update was successful","code":"200"}}';
			}else{
				echo '{"error":{"message":"There seems to be previledge issue Login again", "fatal":"true"}}';
			}
			$qr = null;
		}catch(PDOException $e){
			echo '{"error":{"message":"'.$e->getMessage().'", "fatal":"true"}}';
		}
	}else{
		echo '{"error":{"message":"username and publicKey is required", "fatal":"true"}}';
	}
});
$app->post('/api/user/login', function(Request $req, Response $resp){
	$username = $req->getParam('username');
	$password = $req->getParam('password');
	$key = ' FitSKchgoHOOKing666';
	$string = $key.'34iIlm'.$password.'io9m-';
	$encryptedPassword = hash('sha256', $string);
	$mcrypto = new mcrypt();
	$publicKey = $mcrypto->mCryptThis(time());
	if(isset($username) and isset($password)){
		try{
			$dbn = new db();	
			$sql = "select count(*) from users where username='".$username."' and password='".$encryptedPassword."'";
			$dbn = $dbn->connect();
			$qr = $dbn->query($sql);
			if ($qr->fetchColumn() > 0) {
				$sql = "update users set publicKey = '".$publicKey."' where username = '".$username."' and password = '".$password."'";
				$stmt = $dbn->prepare($sql);
			    $stmt->execute();
			    $_SESSION['sk'] = $publicKey;
				$_SESSION['ji'] = $username;
				echo '{"error":{"message":"", "fatal":"true"}, "success":{"message":"Login successful", "status":"200"}}';
			}else{
				echo '{"error":{"message":"Login credentials match not found", "fatal":"true"}}';
			}
		}catch(PDOException $e){
			echo '{"error":{"message":"'.$e->getMessage().'", "fatal":"true"}}';
		}
	}else{
		echo '{"error":{"message":"Login credentials not found", "fatal":"true"}}';
	}
});
$app->put('/api/user/transaction/exchange/{userID}', function(Request $req, Response $resp){
	$userID = $req->getAttribute('userID');
	$GCinWallet = $req->getParam('GCinWallet');
	$BMCValue = $req->getParam('newBMC');
	try{
		$db = new db();
		$db = $db->connect();
		$sql = "update wallets set BMC = '".$BMCValue."', GCinWallet = '".$GCinWallet."' where userID ='".$userID."'";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		echo '{"error":{"message":"", "fatal":""}, "success":{"message":"Update was successful","code":"200"}}';
	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "status":"fatal"}}';
	}
});
$app->put('/api/user/bitx/{userID}', function(Request $req, Response $resp){
	$userID = $req->getAttribute('userID');
	$fromCurrency = $req->getParam('from');
	$toCurrency = $req->getParam('to');
	$fromValue = $req->getParam('fromValue');
	$suc = false;
	try{
		$sql = "select*from wallets where userID ='".$userID."'";
		$dbn = new db();
		$dbn = $dbn->connect();
		$qr = $dbn->query($sql);
		if ($qr->fetchColumn() == 1){
			$stmt = $dbn->query($sql);
			$data = $stmt->fetchAll(PDO::FETCH_OBJ);
			$strip = $data[0];
			$BMC = ($strip->BMC == '')? 0 : $strip->BMC;
			$GCinWallet = ($strip->GCinWallet == '')? 0 : $strip->GCinWallet;
			$BTC = ($strip->BTC == '')? 0 : $strip->BTC;
			$NGN = ($strip->NGN == '')? 0 : $strip->NGN;

			$nsql = "select*from exchangerate where label = 'NGN'";
			$qr = $dbn->query($nsql);
			$nrow = $qr->fetchAll(PDO::FETCH_OBJ);
			$nrow = $nrow[0];
			$ngnBuyValue = $nrow->exchangeBuyValue;
			$ngnSellValue = $nrow->exchangeSellValue;

			$nsql = "select*from exchangerate where label = 'BMC'";
			$qr = $dbn->query($nsql);
			$nrow = $qr->fetchAll(PDO::FETCH_OBJ);
			$nrow = $nrow[0];
			$BMCBuyValue = $nrow->exchangeBuyValue;
			$BMCSellValue = $nrow->exchangeSellValue;

			$nsql = "select*from exchangerate where label = 'GCinWallet'";
			$qr = $dbn->query($nsql);
			$nrow = $qr->fetchAll(PDO::FETCH_OBJ);
			$nrow = $nrow[0];
			$GCinWalletBuyValue = $nrow->exchangeBuyValue;
			$GCinWalletSellValue = $nrow->exchangeSellValue;

			$nsql = "select*from exchangerate where label = 'BTC'";
			$qr = $dbn->query($nsql);
			$nrow = $qr->fetchAll(PDO::FETCH_OBJ);
			$nrow = $nrow[0];
			$BTCBuyValue = $nrow->exchangeBuyValue;
			$BTCSellValue = $nrow->exchangeSellValue;

			if($fromCurrency == "NGN" || $fromCurrency == "BTC"){
				//Client is buying. Use the sell value
				if($toCurrency == "NGN" || $toCurrency == "BTC"){
					echo '{"error":{"message":"Invalid Transaction, cannot buy NGN with BTC or either", "status":"fatal"}}';
				}else{
					// Lets see if the user has that much
					switch ($fromCurrency){
						case "NGN":
						//User is buying BMC with either NGN or BTC
						if($fromValue <= $NGN){
							//User has enough
							if($toCurrency == "BMC"){
								/*
								/# converting from NGN to BMC, Calculate the BMC buyable using ($fromValue*$BMCSellValue)/ngnSellValue;
								*/
								$BMC = ($fromValue*$BMCSellValue)/$ngnSellValue + $BMC;
								$NGN = $NGN - $fromValue;
								$suc = true;
							}else{
								/*
								/# converting from NGN to GCinWallet, Calculate the BMC buyable using ($fromValue*$GCinWalletSellValue)/$ngnSellValue;
								*/
								$GCinWallet = ($fromValue*$GCinWalletSellValue)/$ngnSellValue + $GCinWallet;
								$NGN = $NGN - $fromValue;
								$suc = true;
							}
						}else{
							//User doesnt have sufficient
							echo '{"error":{"message":"Insufficient Balance '.$NGN.' in account", "status":"fatal"}}';
						}
						break;
						case "BTC":
						if($fromValue <= $BTC){
							//User has enough $BTC 
							if($toCurrency == "BMC"){
								/*
								/# converting from BTC to BMC, Calculate the BMC buyable using ($fromValue*$BMCSellValue)/$BTCSellValue;
								*/
								$BMC = ($fromValue*$BMCSellValue)/$BTCSellValue + $BMC;
								$BTC = $BTC - $fromValue;
								$suc = true;
							}else{
								/*
								/# converting from BTC to GCinWallet, Calculate the BMC buyable using ($fromValue*$GCinWalletSellValue)/$ngnSellValue;
								*/
								$GCinWallet = ($fromValue*$GCinWalletSellValue)/$BTCSellValue + $GCinWallet;
								$BTC = $BTC - $fromValue;
								$suc = true;
							}
						}else{
							//User doesnt have enough
							echo '{"error":{"message":"Insufficient Balance in account", "status":"fatal"}}';
						}
						break;
						default:
						echo '{"error":{"message":"Unknown Transaction", "status":"fatal"}}';
					}
				}
			}else{
				//Client is selling, use buy value
				switch($fromCurrency){
					case "BMC":
					//converting from BMC to either GCinWallet or NGN @TODO BTC
					if($toCurrency <= $BMC*0.6){
						if($toCurrency == "GCinWallet"){
							//Selling BMC back to us for some GCinWallet
							$GCinWallet = ($fromValue*$GCinWalletBuyValue)/$BMCBuyValue + $GCinWallet;
							$BMC = $BMC - $fromValue;
							$suc = true;
						}else{
							//Selling BMC back to us for some NGn
							$NGN = ($fromValue*$NGNBuyValue)/$BMCBuyValue + $NGN;
							$BMC = $BMC - $fromValue;
							$suc = true;
						}
					}else{
						echo '{"error":{"message":"Insufficient Balance in account", "status":"fatal"}}';
					}
					break;
					case "GCinWallet":
					//converting from GCinWallet to either BMC or NGN @TODO BTC
					if($toCurrency <= $GCinWallet*0.6){
						if($toCurrency == "BMC"){
							//Selling BMC back to us for some GCinWallet
							$GCinWallet = ($fromValue*$BMCBuyValue)/$GCinWalletBuyValue + $GCinWallet;
							$BMC = $BMC - $fromValue;
							$suc = true;
						}else{
							//Selling GCinWallet back to us for some NGn
							$NGN = ($fromValue*$NGNBuyValue)/$GCinWalletBuyValue + $NGN;
							$BMC = $BMC - $fromValue;
							$suc = true;
						}
					}else{
						echo '{"error":{"message":"Insufficient Balance in account", "status":"fatal"}}';
					}
					break;
					default:
					echo '{"error":{"message":"Unknown Transaction", "status":"fatal"}}';
				}

			}
			if($suc){
				$sql = "update wallets set BMC = '".$BMC."', GCinWallet = '".$GCinWallet."', NGN = '".$NGN."', BTC ='".$BTC."' where userID ='".$userID."'";
				$stmt = $dbn->prepare($sql);
				$stmt->execute();
			echo '{"error":{"message":"", "status":"fatal"}, "success":{"message":"Transaction was successful","code":"200"}}';
			}
		}else{
			echo '{"error":{"message":"User does not have an account yet", "status":"fatal"}}';
		}
	}catch(PDOException $e){
		echo '{"error":{"message":"'.$e->getMessage().'", "status":"fatal"}}';
	}
});
?>