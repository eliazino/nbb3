<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
$app = new \Slim\App;
error_reporting(0);
ini_set('max_execution_time', 300);
$app->post('/api/hmo/create', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			if(strlen($data->name) > 5 and strlen($data->address) > 5 and strlen($data->phone) > 5 and strlen($data->email) > 5 and is_numeric($data->state) and is_numeric($data->LG)){
				$q = "insert into hmo (name, address, phone, email, dateAdded, state, LG) VALUES (:name, :address, :phone, :email, :dateAdded, :state, :LG)";
				$mcrypto = new mcrypt();
				$ftoken = $mcrypto->mCryptThis(time()*rand(100000,2000000));
				$Qin = $dbn->connect();
				$f = $Qin->prepare($q);
				$f->bindParam(":name", $data->name);
				$f->bindParam(":address", $data->address);
				$f->bindParam(":phone", $data->phone);
				$f->bindParam(":email", $data->email);
				$f->bindParam(":dateAdded", $data->dateAdded);
				$f->bindParam(":state", $data->state);
				$f->bindParam(":LG", $data->LG);
				$f->execute();
				$lid = substr($Qin->lastInsertId(), 0, 1);
				$ftoken = str_split($ftoken);
				$ftoken[count($ftoken)-3] = $lid;
				$ftoken = implode("",$ftoken);
				$g = '{"error":{"message":"", "status":"0"},"success":{"message":"HMO created","status":"200"}, "content":{"HMOID":"'.$Qin->lastInsertId().'", "ftoken":"'.$ftoken.'"}}';
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:'.$e.' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/create/staff', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			if(isset($data->name) and isset($data->address) and isset($data->phone) and isset($data->email) and isset($data->state) and isset($data->LG) and isset($data->hmoID) and isset($data->gender) and isset($data->username) and isset($data->password)){
				$hmo = $data->hmoID;
				if(is_numeric($hmo)){
					$fsql = "select*from hmo where id = '$hmo'";
					if($dbn->isExist($fsql)){
						if(isset($data->ftoken) || (isset($data->adminUsername) and isset($data->adminPublicKey))){
							$auth = false;
							if(isset($data->ftoken)){
								if(substr($data->ftoken, strlen($data->ftoken) - 3, 1) == substr($hmo, 0,1)){
									$auth = true;
								}else{
									$auth = false;
								}
							}else{
								$auth = $dbn->isExist("select*from hmostaffs where username = '$data->adminUsername' and publicKey = '$data->adminPublicKey'");
							}
							if($auth){
								if(!$dbn->isExist("select*from hmostaffs where username = '$data->username'")){
									if(!$dbn->isExist("select*from hmostaffs where email = '$data->email'")){
										$key = ' FitSKchgoHOOKing666';
										$string = $key.'34iIlm'.$data->password.'io9m-';
										$encryptedPassword = hash('sha256', $string);
										$mcrypto = new mcrypt();
										$pkey = $mcrypto->mCryptThis(time()*rand(1000,20000));
										$q = "insert into hmostaffs (name, address, phone, email, state, LG, username, password, publicKey, HMOID, gender) VALUES (:name, :address, :phone, :email, :state, :lg, :username, :password, :publicKey, :HMOID, :gender)";
										$Qin = $dbn->connect();
										$f = $Qin->prepare($q);
										$f->bindParam(":name", $data->name);
										$f->bindParam(":address", $data->address);
										$f->bindParam(":phone", $data->phone);
										$f->bindParam(":email", $data->email);
										$f->bindParam(":state", $data->state);
										$f->bindParam(":lg", $data->LG);
										$f->bindParam(":username", $data->username);
										$f->bindParam(":password", $encryptedPassword);
										$f->bindParam(":publicKey", $pkey);
										$f->bindParam(":HMOID", $hmo);
										$f->bindParam(":gender", $data->gender);
										$f->execute();
										$g = '{"error":{"message":"", "status":"0"},"success":{"message":"HMO staff created","status":"200"}, "content":{"username":"'.$data->username.'", "publicKey":"'.$pkey.'"}}';
									}else{
										$g = '{"error":{"message":"The email cannot be used again", "status":"1"}}';
									}
								}else{
									$g = '{"error":{"message":"The username cannot be used", "status":"1"}}';
								}
							}else{
								$g = '{"error":{"message":"The Auth profile is invalid", "status":"1"}}';
							}

						}else{
							$g = '{"error":{"message":"The Auth profile have not been found", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The HMO have not been found", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO profile is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});



$app->post('/api/hmo/login', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson == NULL)){
		try{
			$data = $validJson->data;
			$username = $data->username;
			$password = $data->password;
			if(isset($username) and isset($password)){
				$key = ' FitSKchgoHOOKing666';
				$string = $key.'34iIlm'.$password.'io9m-';
				$encryptedPassword = hash('sha256', $string);
				$mcrypto = new mcrypt();
				$lastseen = time();
				$publicKey = $mcrypto->mCryptThis(time());
				$sql = "select count(*) from hmostaffs where username='".$username."' and password='".$encryptedPassword."'";
				$Qi = $dbn->connect();
				$f = $Qi->query($sql);
				if ($f->fetchColumn() > 0) {
					$sql = "update hmostaffs set publicKey = '".$publicKey."', lastseen = '".$lastseen."' where username = '".$username."'";
					$stmt = $Qi->prepare($sql);
					$stmt->execute();
					$t = "select other_tbl.HMOID as HMOID, other_tbl.hmoName as hmoName, other_tbl.staffName as staffName, other_tbl.address as address, other_tbl.phone as phone, other_tbl.email as email, other_tbl.gender as gender, other_tbl.username as username, other_tbl.publicKey as publicKey, other_tbl.lastseen as lastseen, lgs.name as lg, other_tbl.state as state from (select user_tbl.HMOID as HMOID, user_tbl.hmoName as hmoName, user_tbl.staffName as staffName, user_tbl.address as address, user_tbl.phone as phone, user_tbl.email as email, user_tbl.gender as gender, user_tbl.username as username, user_tbl.publicKey as publicKey, user_tbl.lastseen as lastseen, user_tbl.lg as lg, state.name as state from (select hmostaffs.HMOID as HMOID, hmo.name as hmoName, hmostaffs.name as staffName, hmostaffs.address, hmostaffs.phone, hmostaffs.email, hmostaffs.gender, hmostaffs.username, hmostaffs.publicKey, hmostaffs.lastseen, hmostaffs.lg, hmostaffs.state from hmostaffs left join hmo on hmostaffs.HMOID = hmo.id where username = '".$username."' and publicKey = '".$publicKey."') as user_tbl left join state on user_tbl.state = state.id) as other_tbl left join lgs on other_tbl.lg = lgs.id";
					$data = $dbn->selectFromQuery($t);
					$datums = json_decode($data);
					$hmoid = $datums->HMOID;
					$company = "select tmp_table.address as address, tmp_table.dateAdded as dateAdded, tmp_table.email as email, tmp_table.HMOID as HMOID, tmp_table.hmoName as hmoName, tmp_table.phone as phone, tmp_table.state as state, tmp_table.totalOrg as totalOrg, tmp_table.totalTransactions totalTransactions, tmp_table.LG as LG, state.name as stateName from (select address, dateAdded, email, hmo.id as HMOID, hmo.name as hmoName, phone, state, totalOrg, totalTransactions, lgs.name as LG, hmo.state as stateID from hmo left join lgs on lgs.id = hmo.LG where hmo.id = '$hmoID') as tmp_table left join state on state.id = tmp_table.stateID";
					$hmoProfile = $dbn->selectFromQuery($t);		
					$g = '{"error":{"message":"", "status":"0"}, "success":{"message":"Login successful", "status":"200"}, "content":{"data":'.$data.', "hmoProfile":'.$hmoProfile.'}}';
				}else{
					$g = '{"error":{"message":"Login credentials match not found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});


$app->post('/api/hmo/create/plan', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			if(isset($data->planName) and isset($data->description) and isset($data->hmoID) and isset($data->username) and isset($data->publicKey)){
				$planName = $data->planName;
				$description = $data->description;
				$hmo = $data->hmoID;
				$username = $data->username;
				$publicKey = $data->publicKey;
				if(is_numeric($hmo)){
					$fsql = "select*from hmostaffs where username = '$username' and publicKey = '$publicKey' and HMOID = '$hmo'";
					if($dbn->isExist($fsql)){
						if(!$dbn->isExist("select*from plans where name = '$planName' and hmoid = '$hmo'")){
							$q = "insert into plans (name, description, hmoid) VALUES (:name, :description, :hmoid)";
							$Qin = $dbn->connect();
							$f = $Qin->prepare($q);
							$f->bindParam(":name", $planName);
							$f->bindParam(":description", $description);
							$f->bindParam(":hmoid", $hmo);						
							$f->execute();
							$g = '{"error":{"message":"", "status":"0"},"success":{"message":"The new Plan created","status":"200"}, "content":{}}';
						}else{
							$g = '{"error":{"message":"Sorry, the plan already exists", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO profile is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});


$app->post('/api/hmo/create/service', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$service = $validJson->data->services;
			$planID = $validJson->data->planID;
			$username = $validJson->data->username;
			$publicKey = $validJson->data->publicKey;
			$hmoID = $validJson->data->hmoID;
			if(isset($service) and isset($planID) and isset($username) and isset($publicKey)){
				if($dbn->isExist("select*from hmostaffs where username = '$username' and publicKey = '$publicKey' and hmoID = '$hmoID'")){
					if(is_numeric($planID)){
						if(count($service) > 1){
							$counter = 0;
							$statuses = array("failed"=>[], "successful"=>[], "duplicate"=>[], "errorLog"=>[]);
							$prep = "delete from planservices where hmoID = '$hmoID' and planID = '$planID'";
							$Qin = $dbn->connect();
							$f = $Qin->prepare($prep);
							$f->execute();
							while($counter < count($service)){
								if(isset($service[$counter]->serviceID) and isset($service[$counter]->category)){
									if(!$dbn->isExist("select*from planservices where serviceID = '".$service[$counter]->serviceID."' and hmoID = '$hmoID' and planID = '$planID'")){
										$f = "insert into planservices (serviceID, planID, hmoID, category) values (:serviceID, :planID, :hmoID, :category)";										
										$f = $Qin->prepare($f);
										$f->bindParam(":serviceID", $service[$counter]->serviceID);
										$f->bindParam(":planID", $planID);
										$f->bindParam(":hmoID", $hmoID);						
										$f->bindParam(":category", $service[$counter]->category);
										$f->execute();
										array_push($statuses["successful"], $service[$counter]->serviceID);
									}else{
										array_push($statuses["duplicate"], $service[$counter]->serviceID);
									}
								}else{
									array_push($statuses["failed"], $service[$counter]->serviceID);
									array_push($statuses["errorLog"], "All Fields are required");
								}
								$counter++;
							}
							$q = $dbn->selectFromQuery("select * from providertariff where planID = '$planID'");
							$statuses = json_encode($statuses);
							$g = '{"error":{"message":"","status":"0"}, "success":{"message":"service creation was succesfull","code":"200"}, "content":{"data":'.$statuses.'}}';
						}else{
							$g = '{"error":{"message":"At least 1 services are required", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The Plan is invalid", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/set/provider/to/{status}', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	$status = $req->getAttribute('status');
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$username = $data->username;
			$publicKey = $data->publicKey;
			$hmo = $data->hmoID;
			$providerID = $data->provider;
			if(isset($username) and isset($publicKey) and isset($hmo) and is_numeric($hmo)){
				if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
					if(isset($providerID) and is_numeric($providerID)){
						if($status == 2){
							$sql = "INSERT providersheet (hmoID, providerID) SELECT 
							:hmo, :provider WHERE NOT EXISTS 
							( SELECT 1 FROM providersheet WHERE hmoID = :hmo AND providerID = :provider )";
							$Qin = $dbn->connect();
							$f = $Qin->prepare($sql);
							$f->bindParam(":hmo", $hmo);
							$f->bindParam(":provider", $providerID);
							$f->execute();
							$g = '{"error":{"message":"","status":"0"}, "success":{"message":"The new status has been set","code":"200"}}';
						}elseif($status == 1){
							$sql = "UPDATE providersheet SET status = 1 WHERE hmoID = :hmo AND providerID = :provider";
							$Qin = $dbn->connect();
							$f = $Qin->prepare($sql);
							$f->bindParam(":hmo", $hmo);
							$f->bindParam(":provider", $providerID);
							$f->execute();
							$g = '{"error":{"message":"","status":"0"}, "success":{"message":"The new status has been set","code":"200"}}';
						}elseif($status == 0){
							$sql = "UPDATE providersheet SET status = 0 WHERE hmoID = :hmo AND providerID = :provider";
							$Qin = $dbn->connect();
							$f = $Qin->prepare($sql);
							$f->bindParam(":hmo", $hmo);
							$f->bindParam(":provider", $providerID);
							$f->execute();
							$g = '{"error":{"message":"","status":"0"}, "success":{"message":"The new status has been set","code":"200"}}';
						}else{
							$g = '{"error":{"message":"The New status could not be set because it is invalid", "status":"1"}}';	
						}
					}else{
						$g = '{"error":{"message":"The Provider is invalid", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/create/tariff', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$tariffName = $data->tariffName;
			$tariffDescription = $data->tariffDescription;
			$planID = $data->planID;
			$hmo = $data->hmoID;
			$providerID = $data->provider;
			$username = $data->username;
			$publicKey = $data->publicKey;
			if(isset($username) and isset($publicKey) and isset($hmo) and is_numeric($hmo)){
				if($dbn->hmoStaffExist($username, $publicKey, $hmo)){					
					if(isset($tariffName) and isset($planID) and isset($providerID) and is_numeric($planID) and isset($tariffDescription)){
						if($dbn->isExist("select*from plans where id = '$planID'")){
							if(!$dbn->isExist("select * from tariffs where planID = '$planID' and providerID = '$providerID' and hmoID = '$hmo' and name = '$tariffName'")){
								$dats = $dbn->selectFromQuery("select*from planservices where planID = '$planID'");
								$sql = "insert into tariffs (description, hmoID, name, planID, providerID) values (:descr, :hmoID, :name, :planID, :providerID)";
								$f = $dbn->connect();
								$db = $f->prepare($sql);
								$db->bindParam(':descr', $tariffDescription);
								$db->bindParam(':hmoID', $hmo);
								$db->bindParam(':name', $tariffName);
								$db->bindParam(':planID', $planID);
								$db->bindParam(':providerID', $providerID);
								$db->execute();
								$tariffID = $f->lastInsertId();
								$dats = json_decode($dats);
								$counter = 0;
								$price = 0;
								while($counter < count($dats)){
									$sql = "insert into providertariff (providerID, planID, HMOID, price, serviceID, tariffID) values (:providerID, :planID, :HMOID, :price, :serviceID, :tariffID)";
									$db = $f->prepare($sql);
									$db->bindParam(':providerID', $providerID);
									$db->bindParam(':planID', $planID);
									$db->bindParam(':HMOID', $hmo);
									$db->bindParam(':price', $price);
									$db->bindParam(':serviceID', $dats[$counter]->serviceID);
									$db->bindParam(':tariffID', $tariffID);
									$db->execute();
									$counter++;
								}
								$g = '{"error":{"message":"","status":"0"}, "success":{"message":"The new tariff has been created","code":"200"}}';
							}else{
								$g = '{"error":{"message":"The Tariff already Exists", "status":"1"}}';
							}							
						}else{
							$g = '{"error":{"message":"The Plan is invalid", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"All fields are required", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/upload/tarrif', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$tariffName = $data->tariffName;
			$planID = $data->planID;
			$hmo = $data->hmoID;
			$providerID = $data->providerID;
			//$price = $data->price;
			$username = $data->username;
			$publicKey = $data->publicKey;
			if(isset($username) and isset($publicKey) and isset($hmo) and is_numeric($hmo)){
				if($dbn->hmoStaffExist($username, $publicKey, $hmo)){					
					if(isset($tariffName) and isset($planID) and isset($providerID) ){
						$services = $data->services;
						if(is_array($services)){
							$counter = 0;
							$Qin = $dbn->connect();
							while($counter < count($services)){
								$price = $services[$counter]->price;
								$serviceID = $services[$counter]->serviceID;
								if(isset($price) and is_numeric($price) and is_numeric($serviceID)){
									$sql = "insert into providerTariff (tariffName, providerID, planID, HMOID, price, serviceID) values (:tariffName, :providerID, :planID, :HMOID, :price, :serviceID)";
									$f = $Qin->prepare($sql);
									$f->bindParam(":tariffName", $tariffName);
									$f->bindParam(":providerID", $providerID);
									$f->bindParam(":planID", $planID);
									$f->bindParam(":HMOID", $hmo);
									$f->bindParam(":price", $price);
									$f->bindParam(":serviceID", $serviceID);
									$f->execute();
									$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Tariff creation was succesfull","code":"200"}, "content":{}}';
								}else{
									$g = '{"error":{"message":"Price and serviceID fields are invalid", "status":"1"}}';
								}
								$counter++;
							}
						}else{
							$g = '{"error":{"message":"The services fields are invalid", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"Some required fields are not valid", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/create/card', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$cards = $data->cards;
			$username = $data->username;
			$publicKey = $data->publicKey;
			$hmo = $data->hmoID;
			if(isset($username) and isset($publicKey)){
				if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
					if(count($cards) > 0){
						$counter = 0;
						$statuses = array("failed"=>[], "successful"=>[], "duplicate"=>[], "errorLog"=>[]);
						while($counter < $count(crads)){
							$cardSerial = $cards[$counter]->cardSeial;
							$dateCreated = $cards[$counter]->dateCreated;
							if(isset($cardSerial) and isset($dateCreated)){
								if(!$dbn->isExist("select*from cards where cardSerial = '$cardSerial' and HMOID = '$hmo'")){
									$sql = "insert into cards (cardSerial, dateCreated, userAssigned, HMOID) values (:cardSerial, :dateCreated, :hmoid)";
									$Qin = $dbn->connect();
									$f = $Qin->prepare($f);
									$f->bindParam(":cardSerial", $cardSerial);
									$f->bindParam(":hmoid", $hmo);
									$f->bindParam(":dateCreated", $dateCreated);
									$f->execute();
									array_push($statuses["successful"], $cardSerial);
								}else{
									array_push($statuses["duplicate"], $cardSerial);
								}
							}else{
								array_push($statuses["failed"], $cardSerial);
								array_push($statuses["errorLog"], "All Fields are required");
							}
							$count++;
						}
						$statuses = json_encode($statuses);
						$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Cards were uploaded succesfully","code":"200"}, "content":{"data":'.$statuses.'}}';
					}else{
						$g = '{"error":{"message":"The cards have not been found", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/create/code', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$code = $data->code;
			$username = $data->username;
			$publicKey = $data->publicKey;
			$enrolee = $data->enrolee;
			$providerID = $data->providerID;
			$hmo = $data->hmoID;
			$comment = $data->comment;
			$transdate = time();
			if(isset($username) and isset($publicKey)){
				if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
					if(isset($code) and isset($enrolee) and isset($providerID) and isset($comment)){
						$sql = "insert into code (code, HMOID, transdate, providerID, enrolee, comment) values (:code, :hmoid, :transdate, :provider, :enrolee, :comment)";
						$Qin = $dbn->connect();
						$f = $Qin->prepare($f);
						$f->bindParam(":code", $code);
						$f->bindParam(":hmoid", $hmo);
						$f->bindParam(":transdate", $transdate);
						$f->bindParam(":provider", $providerID);
						$f->bindParam(":enrolee", $enrolee);
						$f->bindParam(":comment", $comment);
						$f->execute();
						$g = '{"error":{"message":"","status":"0"}, "success":{"message":"The code was created succesfully","code":"200"}, "content":{"code":'.$code.'}}';
					}else{
						$g = '{"error":{"message":"All fields are required", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/create/organization', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$username = $data->username;
			$publicKey = $data->publicKey;
			$hmo = $data->hmoID;
			if(isset($username) and isset($publicKey)){
				if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
					if(isset($data->name) and isset($data->address) and isset($data->phone) and isset($data->email) and isset($data->state) and isset($data->LG)){
						if(!$dbn->isExist("select*from organizations where email = '$data->email'")){
							$q = "insert into organizations (name, address, phone, email, dateAdded, state, LG, HMOID) VALUES (:name, :address, :phone, :email, :dateAdded, :state, :LG, :hmoid)";
							$mcrypto = new mcrypt();
							$ftoken = $mcrypto->mCryptThis(time()*rand(100000,2000000));
							$Qin = $dbn->connect();
							$f = $Qin->prepare($q);
							$f->bindParam(":name", $data->name);
							$f->bindParam(":address", $data->address);
							$f->bindParam(":phone", $data->phone);
							$f->bindParam(":email", $data->email);
							$f->bindParam(":dateAdded", $data->dateAdded);
							$f->bindParam(":state", $data->state);
							$f->bindParam(":LG", $data->LG);
							$f->bindParam(":hmoid", $hmo);
							$f->execute();
							if(!$dbn->isExist("select*from organizations where email = '$data->email'")){
								$q = "insert into organizationstaff (username, password, HMOID, orgID) values (:username, :password, :hmoid, :orgID)";
								$key = ' FitSKchgoHOOKing666';
								$string = $key.'34iIlm'.$data->phone.'io9m-';
								$encryptedPassword = hash('sha256', $string);
								$f = $Qin->prepare($q);
								$f->bindParam(":username", $data->email);
								$f->bindParam(":password", $encryptedPassword);
								$f->bindParam(":hmoid", $hmo);
								$f->bindParam(":orgID", $Qin->lastInsertId());
								$f->execute();
								$g = '{"error":{"message":"", "status":"0"},"success":{"message":"Organization created","status":"200"}, "content":{"username":"'.$data->email.'", "password":"'.$data->phone.'"}}';
							}else{
								$g = '{"error":{"message":"The email exists", "status":"1"}}';
							}							
						}else{
							$g = '{"error":{"message":"The email exists", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:'.$e.' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/create/enrolee', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		$data = $validJson->data;
		$username = $data->username;
		$publicKey = $data->publicKey;
		$hmo = $data->hmoID;
		$orgID = $data->orgID;
		$enrolee = $data->enrolee;
		$key = ' FitSKchgoHOOKing666';
		if(isset($username) and isset($publicKey) and isset($hmo) and isset($orgID)){
			if($dbn->isExist("select * from organizationstaff where username = '$username' and publicKey = '$publicKey' and HMOID = '$hmo' and orgID = '$orgID'")){
				if(is_array($enrolee)){
					$statuses = array("failed"=>[], "successful"=>[], "duplicate"=>[], "failedLog"=>[], "duplicateLog"=>[]);
					$counter = 0;
					while($counter < count($enrolee)){
						$fullname = $enrolee[$counter]->fullname;
						$gender = $enrolee[$counter]->gender;
						$birthday = $enrolee[$counter]->birthday;
						$planID = $enrolee[$counter]->planID;
						$providerID = $enrolee[$counter]->providerID;
						$username = $enrolee[$counter]->username;
						$password = $enrolee[$counter]->password;
						$string = $key.'34iIlm'.$password.'io9m-';
						$encryptedPassword = hash('sha256', $string);
						$cardSerial = $enrolee[$counter]->cardSerial;
						$address = $enrolee[$counter]->address;
						$state = $enrolee[$counter]->state;
						$LG = $enrolee[$counter]->LG;
						$email = $enrolee[$counter]->email;
						$phone = $enrolee[$counter]->phone;
						$nextOfKin  = $enrolee[$counter]->nextOfKin;
						$surgery = $enrolee[$counter]->surgery;
						$existingSurgery = $enrolee[$counter]->existingSurgery;
						$biometric = $enrolee[$counter]->biometric;
						$enroleeID = $enrolee[$counter]->enroleeID;
						$dateCreated = time();
						if(isset($cardSerial) and $dbn->isExist("select*from cards where cardSerial = '$cardSerial' and HMOID = '$hmo'")){
							if(isset($enroleeID) and !$dbn->isExist("select*from enrolee where enroleeID = '$enroleeID'")){
								if(isset($fullname) and isset($phone)){
									if(!$dbn->isExist("select*from enrolee where cardSerial = '$cardSerial'")){
										try{
											$sql = "INSERT INTO `enrolee` (`name`, `gender`, `birthday`, `dependents`, `planID`, `organizationID`, `HMOID`, `username`, `passwords`, `cardSerial`, `address`, `state`, `LG`, `enroleeID`, `email`, `phone`, `nextOfKin`, `surgery`, `existingCondition`, `biometric`, dateCreated) VALUES (:name, :gender, :birthday, :planID, :orgID, :HMOID, :username, :password, :cardSerial, :address, :state, :LG, :enroleeID, :email, :phone,  :nextofkin, :surgery, :existingcond, :biometric, :dateCreated)";
											$qi = $dbn->connect();
											$fa = $qi->prepare($sql);										
											$fa->bindValue(':name', $fullname);
											$fa->bindValue(':gender', $gender);
											$fa->bindValue(':birthday', $birthday);
											$fa->bindValue(':planID', $planID);
											$fa->bindValue(':orgID', $orgID);
											$fa->bindValue(':HMOID', $hmo);
											$fa->bindValue(':username', $username);
											$fa->bindValue(':password', $encryptedPassword);
											$fa->bindValue(':cardSerial', $cardSerial);
											$fa->bindValue(':address', $address);
											$fa->bindValue(':state', $state);
											$fa->bindValue(':LG', $LG);
											$fa->bindValue(':enroleeID', $enroleeID);
											$fa->bindValue(':email', $email);
											$fa->bindValue(':phone', $phone);
											$fa->bindValue(':nextofkin', $nextOfKin);
											$fa->bindValue(':surgery', $surgery);
											$fa->bindValue(':existingcond', $existingSurgery);
											$fa->bindValue(':biometric', $biometric);
											$fa->bindValue(':dateCreated', $dateCreated);
											$fa->execute();
											array_push($statuses["successful"], $enroleeID);
										}catch(PDOException $e){
											$e = $dbn->cleanException($e->getMessage());
											array_push($statuses["failed"], $enroleeID);
											array_push($statuses["failedLog"], $e);											
										}
									}else{
										array_push($statuses["duplicate"], $enroleeID);
										array_push($statuses["duplicateLog"], "The cardSerial exists for a customer");
									}
								}else{
									array_push($statuses["failed"], $enroleeID);
									array_push($statuses["failedLog"], "Some of the required fields are required");
								}
							}else{
								array_push($statuses["duplicate"], $enroleeID);
								array_push($statuses["duplicateLog"], "An enrolee with the ID exist");
							}
						}else{
							array_push($statuses["failed"], $enroleeID);
							array_push($statuses["failedLog"], "The cardSerial is invalid");
						}
						$counter++;
					}
					$statuses = json_encode($statuses);
					$g = '{"error":{"message":"","status":"0"}, "success":{"message":"The creation has been completed","code":"200"}, "content":{"data":'.$statuses.'}}';
				}else{
					$g = '{"error":{"message":"The enrolee data have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"The organization profile have not been found", "status":"1"}}';	
			}
		}else{
			$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
/*	// handle single input with single file upload
	$dbn = new db();
	try{
		$uploadedFiles = $req->getUploadedFiles();
		$uploadedFile = $uploadedFiles['enrolee'];
		if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
			$row = 1;
			$fileX = json_encode($uploadedFile);
			$fileX = json_decode($fileX);
			if (($handle = fopen($fileX->file, "r")) !== FALSE) {
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$num = count($data);
					echo "<p> $num fields in line $row: <br /></p>\n";

					$row++;
					for ($c=0; $c < $num; $c++) {
					//	echo $data[$c] . "<br />\n";
					}
				}
				echo $row;
				fclose($handle);
				$g = '{"error":{"message":"", "status":"0"},"success":{"message":"Organization created","status":"200"}, "content":{"filename":""}}';
			}else{
				$g = '{"error":{"message":"The file is invalid", "status":"1"}}';
			}
			//$filename = moveUploadedFile($directory, $uploadedFile);
			//$g = '{"error":{"message":"", "status":"0"},"success":{"message":"Organization created","status":"200"}, "content":{"filename":""}}';
		}else{
			$g = '{"error":{"message":"The file is invalid", "status":"1"}}';
		}
	}catch(Exception $e){
		$e = $dbn->cleanException($e->getMessage());
		$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
	}*/
	return $dbn->responseFormat($resp,$g);
});

/*
$app->post('/api/hmo/create/organization', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			
			if(isset($data->username) and isset($data->publicKey) and isset($data->hmoID)){
				if($dbn->hmoStaffExist($data->username, $data->publicKey, $data->hmoID)){
					if(isset($data->name) and isset($data->address) and isset($data->phone) and isset($data->email) and isset($data->state) and isset($data->LG) and isset($data->hmoID)){
						$q = "insert into organizations (name, address, phone, email, dateAdded, state, LG, HMOID) VALUES (:name, :address, :phone, :email, :dateAdded, :state, :LG, :hmoid)";
						$Qin = $dbn->connect();
						$f = $Qin->prepare($q);
						$f->bindParam(":name", $data->name);
						$f->bindParam(":address", $data->address);
						$f->bindParam(":phone", $data->phone);
						$f->bindParam(":email", $data->email);
						$f->bindParam(":dateAdded", $data->dateAdded);
						$f->bindParam(":state", $data->state);
						$f->bindParam(":LG", $data->LG);
						$f->bindParam(":hmoid", $data->hmoID);
						$f->execute();
						$g = '{"error":{"message":"", "status":"0"},"success":{"message":"Organization created","status":"200"}, "content":{}}';
					}else{
						$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});
*/

$app->post('/api/hmo/create/organizationStaff', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			if(isset($data->name) and isset($data->address) and isset($data->phone) and isset($data->email) and isset($data->state) and isset($data->LG) and isset($data->hmoID) and isset($data->gender) and isset($data->username) and isset($data->password) and isset($data->orgID)){
				$hmo = $data->hmoID;
				$orgID = $data->orgID;
				if(is_numeric($hmo)){
					$fsql = "select*from organizations where id = '$orgID'";
					if($dbn->isExist($fsql)){
						if((isset($data->adminUsername) and isset($data->adminPublicKey))){
							$auth = false;
							if(false){
							}else{
								$auth = $dbn->isExist("select*from hmostaffs where username = '$data->adminUsername' and publicKey = '$data->adminPublicKey'");
							}
							if($auth){
								if(!$dbn->isExist("select*from organizationstaff where username = '$data->username'")){
									if(!$dbn->isExist("select*from organizationstaff where email = '$data->email'")){
										$key = ' FitSKchgoHOOKing666';
										$string = $key.'34iIlm'.$data->password.'io9m-';
										$encryptedPassword = hash('sha256', $string);
										$mcrypto = new mcrypt();
										$pkey = $mcrypto->mCryptThis(time()*rand(1000,20000));
										$q = "insert into organizationstaff (name, address, phone, email, state, LG, username, password, publicKey, HMOID, gender, orgID) VALUES (:name, :address, :phone, :email, :state, :lg, :username, :password, :publicKey, :HMOID, :gender, :orgID)";
										$Qin = $dbn->connect();
										$f = $Qin->prepare($q);
										$f->bindParam(":name", $data->name);
										$f->bindParam(":address", $data->address);
										$f->bindParam(":phone", $data->phone);
										$f->bindParam(":email", $data->email);
										$f->bindParam(":state", $data->state);
										$f->bindParam(":lg", $data->LG);
										$f->bindParam(":username", $data->username);
										$f->bindParam(":password", $encryptedPassword);
										$f->bindParam(":publicKey", $pkey);
										$f->bindParam(":HMOID", $hmo);
										$f->bindParam(":gender", $data->gender);
										$f->bindParam(":orgID", $data->gender);
										$f->execute();
										$g = '{"error":{"message":"", "status":"0"},"success":{"message":"Organization staff created","status":"200"}, "content":{"username":"'.$data->username.'", "publicKey":"'.$pkey.'"}}';
									}else{
										$g = '{"error":{"message":"The email cannot be used again", "status":"1"}}';
									}
								}else{
									$g = '{"error":{"message":"The username cannot be used", "status":"1"}}';
								}
							}else{
								$g = '{"error":{"message":"The Auth profile is invalid", "status":"1"}}';
							}

						}else{
							$g = '{"error":{"message":"The Auth profile have not been found", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The HMO have not been found", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO profile is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/concludetransaction', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$hmoID = $data->hmoID;
			$providerID = $data->providerID;
			$transactionID = $data->transactionID;
			$status = $data->status;
			$username = $data->username;
			$publicKey = $data->publicKey;
			$comments = $data->comments;
			if(isset($username) and isset($publicKey) and isset($hmoID)){
				if($dbn->hmoStaffExist($data->username, $data->publicKey, $data->hmoID)){
					if(isset($providerID) and isset($transactionID) and isset($status)){
						if($status == 1 or $status = -1){
							$r = $dbn->selectFromQuery("select*from transactions where HMOID = '$hmoid' and providerID = '$providerID' and transID = '$transactionID'");
							$rar = json_decode($r);
							if($count($rar) > 0){
								$amount = $rar->price;
								$s = "update transactions set status = :status, comment = :comment where id = :id and providerID = :pid and HMOID = : hmoid";
								$Qin = $dbn->connect();
								$f = $Qin->prepare($s);
								$f->bindParam(":status", $status);
								$f->bindParam(":id", $transactionID);
								$f->bindParam(":pid", $providerID);
								$f->bindParam(":hmoid", $hmoID);						
								$f->bindParam(":comment", $comment);						
								$f->execute();							
								if($status == -1){
									$rek = "update providersheet set unsettled = unsettled - :amount,  reject = reject + :amount where hmoID = :hmo and providerID = :providerID";
								}elseif($status == 1){
									$rek = "update providersheet set unsettled = unsettled - :amount,  settled = settled + :amount where hmoID = :hmo and providerID = :providerID";
								}
								$f = $Qin->prepare($rek);
								$f->bindParam(":amount", $amount);
								$f->bindParam(":hmo", $hmoID);
								$f->bindParam(":providerID", $providerID);											
								$f->execute();
							}else{
								$g = '{"error":{"message":"The transaction is invalid", "status":"1"}}';
							}
						}else{
							$g = '{"error":{"message":"The transaction Has not been altered! The status is the old status", "status":"1"}}';
						}						
					}else{
						$g = '{"error":{"message":"All fields are required", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The Auth profile is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/hmo/sync/encounter', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		$data = $validJson->data;
		$username = $data->username;
		$publicKey = $data->publicKey;
		$providerID = $data->providerID;
		$encounter = $data->encounter;
		$transBatch = time()."/".$providerID."/".rand(23456,989999);
		$syncDate = time();
		$provData = $dbn->selectFromQuery("select*from providers where id = '$providerID'");
		$pname = $provData->name;
		if(isset($username) and isset($publicKey) and isset($hmo) and isset($providerID)){
			if($dbn->isExist("select * from provider where username = '$username' and publicKey = '$publicKey' and providerID = '$hmo'")){
				if(is_array($encounter)){
					$statuses = array("failed"=>[], "successful"=>[], "duplicate"=>[], "failedLog"=>[], "duplicateLog"=>[]);
					$emails = array();
					$counter = 0;
					$t = "INSERT INTO transbatch (totalPrice, providerID, transDate, transID) values (0, :providerID, :transDate, :transID)";
					try{
						$qi = $dbn->connect();
						$fa = $qi->prepare($t);										
						$fa->bindValue(':providerID', $providerID);
						$fa->bindValue(':transDate', $syncDate);
						$fa->bindValue(':transID', $transBatch);
						$fa->execute();
						while($counter < count($encounter)){
							$enroleeID = $encounter[$counter]->enroleeID;
							$price = $encounter[$counter]->amount;
							$planID = $encounter[$counter]->planID;
							$PACode = $encounter[$counter]->PaCode;
							$transDate = $encounter[$counter]->transDate;
							$cardSerial = $encounter[$counter]->cardSerial;
							$orgID = $encounter[$counter]->orgID;
							$summary = $encounter[$counter]->summary;
							$comment = $encounter[$counter]->comment;
							$transID = $encounter[$encounter]->transID;
							$hmoID = $encounter[$encounter]->hmoID;
							if($dbn->isExist("select*from providersheet where hmoID = '$hmoID' and providerID = '$providerID'")){
								if(isset($price) and isset($enroleeID) and isset($hmoID) and isset($planID)){
									if($dbn->isExist("select*from transactions where transID '$transID' and enroleeID = '$enroleeID'")){
										if($dbn->isExist("select * from enrolee where enroleeID = '$enroleeID' and HMOID = '$hmoID' active = 1")){
											$sql = "insert into transactions (batchID, cardSerial, code, comments, enroleeID, HMOID, orgID, planID, price, providerID, summary, syncDate, transDate, transID) values (:batchID, :cardSerial, :code, :comments, :enroleeID, :HMOID, :orgID, :planID, :price, :providerID, :summary, :syncDate, :transDate, :transID)";
											try{
												$qi = $dbn->connect();
												$fa = $qi->prepare($sql);										
												$fa->bindValue(':batchID', $transBatch);
												$fa->bindValue(':cardSerial', $cardSerial);
												$fa->bindValue(':code', $PACode);
												$fa->bindValue(':comments', $comments);
												$fa->bindValue(':enroleeID', $enroleeID);
												$fa->bindValue(':HMOID', $hmoID);
												$fa->bindValue(':orgID', $orgID);
												$fa->bindValue(':planID', $planID);
												$fa->bindValue(':price', $price);
												$fa->bindValue(':providerID', $providerID);
												$fa->bindValue(':summary', $summary);
												$fa->bindValue(':syncDate', $syncDate);
												$fa->bindValue(':transDate', $transDate);
												$fa->bindValue(':transID', $transID);											
												$fa->execute();
												array_push($statuses["successful"], $transID);
												$sp = "update providersheet set unsettled = unsettled + :price where HMOID = :hmoID and providerID = :providerID";
												$fa = $qi->prepare($sql);
												$fa->bindValue(':price', $price);
												$fa->bindValue(':HMOID', $hmoID);
												$fa->bindValue(':providerID', $providerID);
												$fa->execute();
												$data = $dbn->selectFromQuery("select*from hmo where id = '$hmoID'");
												$data = json_encode($data);
												$email = $data->email;
												if(!in_array($email, $emails)){
													array_push($emails, $email);
												}												
											}catch(PDOException $e){
												$e = $dbn->cleanException($e->getMessage());
												array_push($statuses["failed"], $transID);
												array_push($statuses["failedLog"], $e);
											}
										}else{
											array_push($statuses["failed"], $transID);
											array_push($statuses["failedLog"], "The enrolee is invalid");
										}
									}else{
										array_push($statuses["duplicate"], $transID);
										array_push($statuses["duplicateLog"], "Transaction with the enroleeID and transactionID exist");
									}	
								}else{
									array_push($statuses["failed"], $transID);
									array_push($statuses["failedLog"], "The transaction does not have all required field");
								}	
							}else{
								array_push($statuses["failed"], $transID);
								array_push($statuses["failedLog"], "The Provider is not implemented, Contact HMO");
							}						
							$counter++;
						}
						$counter = 0;
						while($counter < count($emails)){
							$dbn->postMaster($transBatch, $pname, $emails[$counter]);
							$counter++;
						}
						$statuses = json_encode($statuses);
						$g = '{"error":{"message":"","status":"0"}, "success":{"message":"The creation has been completed","code":"200"}, "content":{"data":'.$statuses.'}}';
					}catch(PDOException $e){
						$e = $dbn->cleanException($e->getMessage());
						$g = '{"error":{"message":"An error:'.$e.' Ocurred", "status":"1"}}';
					}					
				}else{
					$g = '{"error":{"message":"The enrolee data have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"The organization profile have not been found", "status":"1"}}';	
			}
		}else{
			$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});


/* The spot for updates */


$app->put('/api/hmo/edit', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$username = $data->username;
			$publicKey =  $data->publicKey;
			$hmoID = $data->hmoID;
			if(isset($username) and isset($publicKey) and isset($hmoID)){
				if($dbn->hmoStaffExist($data->username, $data->publicKey, $data->hmoID)){
					if(isset($data->name) and isset($data->address) and isset($data->phone) and isset($data->email) and isset($data->state) and isset($data->LG)){
						$q = "update hmo set name = :name , address = :address, phone = :phone, email = :email, state = :state, LG = :LG where id = :hmoID";
						$Qin = $dbn->connect();
						$f = $Qin->prepare($q);
						$f->bindParam(":name", $data->name);
						$f->bindParam(":address", $data->address);
						$f->bindParam(":phone", $data->phone);
						$f->bindParam(":email", $data->email);
						$f->bindParam(":state", $data->state);
						$f->bindParam(":LG", $data->LG);
						$f->bindParam(":hmoID", $hmoID);
						$f->execute();
						$g = '{"error":{"message":"", "status":"0"},"success":{"message":"HMO Updated","status":"200"}, "content":{}}';
					}else{
						$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The Auth profile is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:'.$e.' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});


$app->put('/api/hmo/edit/staff', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			if(isset($data->name) and isset($data->address) and isset($data->phone) and isset($data->email) and isset($data->state) and isset($data->LG) and isset($data->hmoID) and isset($data->gender)){
				$hmo = $data->hmoID;
				if(is_numeric($hmo)){
					$fsql = "select*from hmo where id = '$hmo'";
					if($dbn->isExist($fsql)){
						if(isset($data->ftoken) || (isset($data->username) and isset($data->publicKey))){
							$auth = false;
							if(false){
							}else{
								$auth = $dbn->isExist("select*from hmostaffs where username = '$data->username' and publicKey = '$data->publicKey'");
							}
							if($auth){
								if(true){
									if(true){
										$addPassword = false;
										if(isset($data->password) and strlen($data->password) > 5){
											$key = ' FitSKchgoHOOKing666';
											$string = $key.'34iIlm'.$data->password.'io9m-';
											$encryptedPassword = hash('sha256', $string);
											$patch = " ,password = :password";
											$addPassword = true;
										}
										$q = "update hmostaffs set name = :name, address = :address, phone = :phone, email = :email, state = :state, LG = :LG, gender = :gender".$patch." where username = :username and publicKey = :publicKey";
										$Qin = $dbn->connect();
										$f = $Qin->prepare($q);
										$f->bindParam(":name", $data->name);
										$f->bindParam(":address", $data->address);
										$f->bindParam(":phone", $data->phone);
										$f->bindParam(":email", $data->email);
										$f->bindParam(":state", $data->state);
										$f->bindParam(":lg", $data->LG);
										$f->bindParam(":username", $data->username);
										if($addPassword){ $f->bindParam(":password", $encryptedPassword);}
										$f->bindParam(":publicKey", $pkey);
										$f->bindParam(":gender", $data->gender);
										$f->execute();
										$g = '{"error":{"message":"", "status":"0"},"success":{"message":"HMO staff Profile updated","status":"200"}, "content":{}}';
									}else{
										$g = '{"error":{"message":"The email cannot be used again", "status":"1"}}';
									}
								}else{
									$g = '{"error":{"message":"The username cannot be used", "status":"1"}}';
								}
							}else{
								$g = '{"error":{"message":"The Auth profile is invalid", "status":"1"}}';
							}

						}else{
							$g = '{"error":{"message":"The Auth profile have not been found", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The HMO have not been found", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO profile is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});


$app->put('/api/hmo/edit/service', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$service = $validJson->data->services;
			$planID = $validJson->data->planID;
			$username = $validJson->data->username;
			$publicKey = $validJson->data->publicKey;
			$hmoID = $validJson->data->hmoID;
			if(isset($service) and isset($planID) and isset($username) and isset($publicKey)){
				if($dbn->isExist("select*from hmostaffs where username = '$username' and publicKey = '$publicKey' and hmoID = '$hmo'")){
					if(is_numeric($planID)){
						if(count($service) > 1){
							$counter = 0;
							$statuses = array("failed"=>[], "successful"=>[], "duplicate"=>[], "errorLog"=>[]);
							while($counter < count($service)){
								if(isset($service[$counter]->serviceID) and isset($service[$counter]->planID) and isset($service[$counter]->category)){
									if(!$dbn->isExist("select*from planservices where serviceID = '$service->serviceID' and hmoID = '$service->hmoID' and planID = '$service->planID'")){
										$dsql = "delete from planservices where planID = '$planID' and hmoID = '$hmoID'";
										$f = $Qin->prepare($f);								
										$f->execute();
										$f = "insert into planservices (serviceID, planID, hmoID, category) values (:serviceID, :planID, :hmoID, :category)";
										$Qin = $dbn->connect();
										$f = $Qin->prepare($f);
										$f->bindParam(":serviceID", $service[$counter]->serviceID);
										$f->bindParam(":planID", $service[$counter]->planID);
										$f->bindParam(":hmoID", $service[$counter]->hmoID);						
										$f->bindParam(":category", $service[$counter]->category);
										$f->execute();
										array_push($statuses["successful"], $service[$counter]->serviceID);
									}else{
										array_push($statuses["duplicate"], $service[$counter]->serviceID);
									}
								}else{
									array_push($statuses["failed"], $service[$counter]->serviceID);
									array_push($statuses["errorLog"], "All Fields are required");
								}
								$counter++;
							}
							$statuses = json_encode($statuses);
							$g = '{"error":{"message":"","status":"0"}, "success":{"message":"service creation was succesfull","code":"200"}, "content":{"data":'.$statuses.'}}';
						}else{
							$g = '{"error":{"message":"At least 1 services are required", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The Plan is invalid", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->put('/api/hmo/edit/tarrif', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			$tariffName = $data->tariffName;
			$planID = $data->planID;
			$hmo = $data->hmoID;
			$providerID = $data->providerID;
			$price = $data->price;
			$username = $data->username;
			$publicKey = $data->publicKey;
			if(isset($username) and isset($publicKey) and isset($hmo) and is_numeric($hmo)){
				if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
					if(isset($price) and is_numeric($price)){
						if(isset($tariffName) and isset($planID) and isset($providerID)){
							$sql = "update providerTariff set tariffName = :tariffName, price = :price where providerID = :providerID and planID = :planID and HMOID = :HMOID";
							$Qin = $dbn->connect();
							$f = $Qin->prepare($sql);
							$f->bindParam(":tariffName", $tariffName);
							$f->bindParam(":providerID", $providerID);
							$f->bindParam(":planID", $planID);						
							$f->bindParam(":HMOID", $hmo);
							$f->bindParam(":price", $price);
							$f->execute();
							$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Tariff update was succesfull","code":"200"}, "content":{}}';
						}else{
							$g = '{"error":{"message":"All fields are required", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The Price is invalid", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->put('/api/hmo/edit/plan', function(Request $req, Response $resp){
	$json = $req->getParsedBody();
	$json = isset($json)? $json : $req->getBody();
	$dbn = new db();
	$validJson = $dbn->jsonFormat($json);
	if(!($validJson === NULL)){
		try{
			$data = $validJson->data;
			if(isset($data->name) and isset($data->description) and isset($data->hmoID) and isset($data->username) and isset($data->publicKey)){
				$planName = $data->name;
				$description = $data->description;
				$id = $data->id;
				$hmo = $data->hmoID;
				$username = $data->username;
				$publicKey = $data->publicKey;
				if(is_numeric($hmo)){
					$fsql = "select*from hmostaffs where username = '$username' and publicKey = '$publicKey' and HMOID = '$hmo'";
					if($dbn->isExist($fsql)){
						if(is_numeric($id) and $dbn->isExist("select*from plans where id = '$id' and hmoid = '$hmo'")){
							$q = "update plans set name = :name, description = :description where id = :id";
							$Qin = $dbn->connect();
							$f = $Qin->prepare($q);
							$f->bindParam(":name", $planName);
							$f->bindParam(":description", $description);
							$f->bindParam(":id", $id);						
							$f->execute();
							$g = '{"error":{"message":"", "status":"0"},"success":{"message":"The new Plan has been updated","status":"200"}, "content":{}}';
						}else{
							$g = '{"error":{"message":"Sorry, the plan is invalid", "status":"1"}}';
						}
					}else{
						$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
					}
				}else{
					$g = '{"error":{"message":"The HMO profile is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
			}
		}catch(PDOException $e){
			$e = $dbn->cleanException($e->getMessage());
			$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"The parameter is not a valid object", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

/*
 This is the getting Arena 
 The getting APIs
*/


$app->get('/api/hmo/get/dashboard',  function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$search = $req->getAttribute('id');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if(is_numeric($hmo) and $dbn->hmoStaffExist($username, $publicKey, $hmo)){
			$enrolees = $dbn->queryCount("select id from enrolee where HMOID = '$hmo'");
			$orgNumber = $dbn->queryCount("select id from organizationstaff where HMOID = '$hmo'");
			$providers = $dbn->queryCount("select id from providerSheet where hmoID = '$hmo'");
			$resolvedInvoice = $dbn->queryCount("select id from transactions where HMOID = '$hmo' and (status = -1 or status = 1)");
			$rejectedInvoice = $dbn->queryCount("select id from transactions where HMOID = '$hmo' and (status = -1)");
			$pendingInvoice = $dbn->queryCount("select id from transactions where HMOID = '$hmo' and (status = 0)");
			$am = $dbn->selectFromQuery("select sum(price) as totalAmount from transactions where HMOID = '$hmo' and status = 1");
			$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"enrolees":"'.$enrolees.'", "providers":"'.$providers.'", "orgCount":"'.$orgNumber.'", "resolvedInvoice":"'.$resolvedInvoice.'", "rejectedInvoice":"'.$rejectedInvoice.'", "pendingInvoice":"'.$pendingInvoice.'", "amountProcessed":'.$am.'}}';
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/staff/{id}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$search = $req->getAttribute('id');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			$p = "";
			if($search == "-"){
				
			}elseif(is_numeric($search)){
				$p = "where id = '$search'";
			}else{
				$p = "where hmostaffs.username = '$username' and hmostaffs.publicKey = '$publicKey'";
			}
			$t = "select other_tbl.HMOID as HMOID, other_tbl.hmoName as hmoName, other_tbl.staffName as staffName, other_tbl.address as address, other_tbl.phone as phone, other_tbl.email as email, other_tbl.gender as gender, other_tbl.username as username, other_tbl.publicKey as publicKey, other_tbl.lastseen as lastseen, lgs.name as lg, other_tbl.state as state from (select user_tbl.HMOID as HMOID, user_tbl.hmoName as hmoName, user_tbl.staffName as staffName, user_tbl.address as address, user_tbl.phone as phone, user_tbl.email as email, user_tbl.gender as gender, user_tbl.username as username, user_tbl.publicKey as publicKey, user_tbl.lastseen as lastseen, user_tbl.lg as lg, state.name as state from (select hmostaffs.HMOID as HMOID, hmo.name as hmoName, hmostaffs.name as staffName, hmostaffs.address, hmostaffs.phone, hmostaffs.email, hmostaffs.gender, hmostaffs.username, hmostaffs.publicKey, hmostaffs.lastseen, hmostaffs.lg, hmostaffs.state from hmostaffs left join hmo on hmostaffs.HMOID = hmo.id ".$p.") as user_tbl left join state on user_tbl.state = state.id) as other_tbl left join lgs on other_tbl.lg = lgs.id";
			try{
				$data = $dbn->selectFromQuery($t);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}catch(PDOException $e){
				$e = $dbn->cleanException($e->getMessage());
				$g = '{"error":{"message":"An error:\''.$e.'\' Ocurred", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/plans/{planID}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$search = $req->getAttribute('planID');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			$sql = "";
			if(is_numeric($search)){
				$sql = "select * from plans where hmoid = '$hmo' and id = '$search'";
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}elseif($search == "-"){
				$sql = "select * from plans where hmoid = '$hmo'";
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				$g = '{"error":{"message":"The search term is invalid", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/serviceCategory', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			$sql = "select * from servicecategory";
			$data = $dbn->selectFromQuery($sql);
			$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});
$app->get('/api/hmo/get/providerCategory', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			$sql = "select * from providercategory";
			$data = $dbn->selectFromQuery($sql);
			$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/services/{serviceID}/{lastPage}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$search = $req->getAttribute('serviceID');
	$lastpage = $req->getAttribute('lastPage');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			if($search == "-"){
				$sql = "select * from services";
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}elseif(is_numeric($search)){
				if(is_numeric($lastPage)){
					$sql = "select * from services where serviceType = '$search' and id > $lastPage order by id asc limit 100";
				}else{
					$sql = "select * from services where serviceType = '$search' order by id asc limit 100";
				}
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				$g = '{"error":{"message":"The search term is invalid", "status":"1"}}';	
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/planservice/{planID}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$search = $req->getAttribute('planID');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			if(is_numeric($search)){
				$sql = "select planservices.category as serviceType, planservices.serviceID as id, services.serviceName as serviceName from planservices left join services on planservices.serviceID = services.id where planservices.planID = '$search' and planservices.hmoID = '$hmo'";
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				$g = '{"error":{"message":"The search term is invalid", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/providertariffs/{planID}/{providerID}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$planID = $req->getAttribute('planID');
	$providerID =$req->getAttribute('providerID');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			if(is_numeric($planID) || $planID == "-"){
				if(is_numeric($providerID) || $providerID == "-"){
					$psql = "";
					if(is_numeric($planID)){
						$psql = $psql."providertariff.planID = '$planID'";
					}
					if(is_numeric($providerID)){
						$psql = $psql."providertariff.providerID = '$providerID'";
					}
					if(strlen($psql) > 2){
						$sql = "select tariffname, HMOID, planID, price, providerID from providertariff where HMOID = '$hmo' and ".$psql;
					}else{
						$sql = "select tariffname, HMOID, planID, price, providerID from providertariff where HMOID = '$hmo'";
					}
					$data = $dbn->selectFromQuery($sql);
					$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
				}else{
					$g = '{"error":{"message":"The search term is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"The search term is invalid", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/providers/{category}/{state}/{lg}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$category = $req->getAttribute('category');
	$state = $req->getAttribute('state');
	$lg = $req->getAttribute('lg');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			if((is_numeric($category) || $category == "-") or (is_numeric($state) or $state == "-") or (is_numeric($lg) or $lg == "-")){
				$plug = "";
				if($category != "-"){ $plug = " where providers.category = '$category'"; }
				if($state != "-"){ $plug = (strlen($plug) > 4)? $plug." and providers.state = '$state'" : " where providers.state = '$state'"; }
				if($lg != "-"){ $plug = (strlen($plug) > 4)? $plug." and providers.LG = '$lg'" : " where providers.LG = '$lg'"; }
				$sql = "SELECT tbl_2.providerID, tbl_2.providersName, tbl_2.providersPhone, tbl_2.LG, tbl_2.providersAddress, tbl_2.providerState, tbl_2.providerLG, providersheet.id as sheetID, providerSheet.status as providerStatus from (SELECT tbl_1.providerID, tbl_1.providersName, tbl_1.providersPhone, tbl_1.LG, tbl_1.providersAddress, tbl_1.providerState, lgs.name as providerLG from (select providers.id as providerID, providers.name as providersName, providers.phone as providersPhone, providers.state as state, providers.LG as LG, providers.address as providersAddress, state.name as providerstate from providers left join state on providers.state = state.id".$plug.") as tbl_1 left join lgs on tbl_1.LG = lgs.id) as tbl_2 left join providersheet on providersheet.providerID = tbl_2.providerID";
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				$g = '{"error":{"message":"The search term is invalid", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/providersheet', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$dbn = new db();
	if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if(is_numeric($hmo)){
			if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
				$sql = "select providersheet.providerID, providers.name, providers.category, providers.id as id from providersheet left join providers on providersheet.providerID = providers.id where providersheet.hmoID = '$hmo'";
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/transactions/{provider}/{cardSerial}/{from}/{to}/{type}/{orgID}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$provider = $req->getAttribute('provider');
	$cardSerial = $req->getAttribute('cardSerial');
	$from = intval($req->getAttribute("from"));
	$dbn = new db();
	$to = ($req->getAttribute("to") > time())? strtotime('today midnight') : $req->getAttribute("to") + 86399;
	$type = $req->getAttribute('type');
	$orgID = $req->getAttribute('orgID');
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			if(is_numeric($provider) || $provider == "-"){
				if(is_numeric($from)){
					if(is_numeric($to)){
						if(is_numeric($provider) || $provider == "-"){
							if(is_numeric($type)){
								$binds = array();
								$binds[":from"] = $from;
								$binds[":to"] = $to;
								$binds[":hmoid"] = $hmo;
								$ssq = "where transactions.transDate >= :from and transactions.transDate <= :to and transactions.HMOID = :hmoid";
								if($provider != "-"){ $ssq = $ssq." and transactions.providerID = :provider"; $binds[":provider"] = $provider; }
								if($cardSerial != "-"){$ssq = $ssq." and transactions.cardSerial = :cardSerial"; $binds[":cardSerial"] = $cardSerial; }
								if($type != "-"){$ssq = $ssq." and transactions.status = :status"; $binds[":status"] = $type; }
								if($orgID != "-"){$ssq = $ssq." and transactions.orgID = :orgID"; $binds[":orgID"] = $orgID; }
								$sql = "select tmp_tbl.batchID, tmp_tbl.benefID, tmp_tbl.cardSerial, tmp_tbl.code, tmp_tbl.comments, tmp_tbl.hmoid, tmp_tbl.transPID, tmp_tbl.orgID, tmp_tbl.planID, tmp_tbl.price, tmp_tbl.providerID, tmp_tbl.status, tmp_tbl.summary,tmp_tbl.transDate, tmp_tbl.transID, tmp_tbl.primaryEnrolee, dependents.name as secondaryEnrolee from (select transactions.batchID as batchID, transactions.benefID as benefID, transactions.cardSerial as cardSerial, transactions.code as code, transactions.comments as comments, transactions.HMOID as hmoid, transactions.id as transPID, transactions.orgID as orgID, transactions.planID as planID, transactions.price as price, transactions.providerID as providerID, transactions.status as status, transactions.summary as summary, transactions.transDate as transDate, transactions.transID as transID, enrolee.name as primaryEnrolee from transactions left join enrolee on enrolee.cardSerial = transactions.cardSerial ".$ssq.") as tmp_tbl left join dependents on tmp_tbl.cardSerial = dependents.cardSerial";
								$f = $dbn->connect();
								$f = $f->prepare($q);							
								$f->execute($binds);
								$row = $f->fetchAll();
								if($row){
									$data = json_encode($row, true);
								}else{
									$data = "[]";
								}
								$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":'.$data.'}}';

							}else{
								$g = '{"error":{"message":"The search term (Type) is invalid", "status":"1"}}';		
							}
						}else{
							$g = '{"error":{"message":"The search term (Provider) is invalid", "status":"1"}}';		
						}
					}else{
						$g = '{"error":{"message":"The search term (To) is invalid", "status":"1"}}';	
					}
				}else{
					$g = '{"error":{"message":"The search term (From) is invalid", "status":"1"}}';
				}
			}else{
				$g = '{"error":{"message":"The search term (Provider) is invalid", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/enrolee/{type}/{cardSerial}/{orgID}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$cardSerial = $req->getAttribute('cardSerial');
	$type = $req->getAttribute('type');
	$orgID = $req->getAttribute('orgID');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			if(true){
				$binds = array();
				$binds[":hmoid"] = $hmo;
				$plug = "";
				if($cardSerial != '-'){ $plug = " and enrolee.cardSerial = :cardSerial";  $binds[":cardSerial"] = $cardSerial; }
				if($orgID != '-'){ $plug = $plug." and enrolee.organizationID = :orgID"; $binds[":orgID"] = $orgID; }
				$sql = "select enrolee.id as enroleeID, address, birthday, cardSerial, dependents, email, enroleeID, gender, HMOID, LG, name, organizationID, phone, planID, profilePicture, publicKey, state, active, organizations.name as orgName from enrolee left join organization on enrolee.organizationID = organization.id where enrolee.HMOID = :hmoid".$plug;
				$f = $dbn->connect();
				$f = $f->prepare($sql);							
				$f->execute($binds);
				$row = $f->fetchAll();
				if($row){
					$data = json_encode($row, true);
				}else{
					$data = "[]";
				}
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				if($cardSerial != '-'){

				}else{
					$g = '{"error":{"message":"Search is required for secondary enrolee", "status":"1"}}';
				}
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});

$app->get('/api/hmo/get/tariffs/{provider}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$providerID = $req->getAttribute('provider');
	$dbn = new db();
    if(array_key_exists('HTTP_PUBLICKEY', $headers) and array_key_exists('HTTP_USERNAME', $headers) and array_key_exists('HTTP_HMOID', $headers)) {
        $publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];
		if($dbn->hmoStaffExist($username, $publicKey, $hmo)){
			if(true){
				$binds = array();
				$binds[":hmoid"] = $hmo;
				$plug = " where tariffs.hmoID = :hmoid";
				if($providerID != '-'){ $plug = $plug." and tariffs.providerID = :provider"; $binds[":provider"] = $providerID; }
				$sql = "select tbl_1.description as description, tbl_1.tariffName, tbl_1.planID, tbl_1.providerID, tbl_1.planName, providers.name as providername from (select tariffs.description as description, tariffs.name as tariffName, tariffs.planID as planID, tariffs.providerID as providerID, plans.name as planName from tariffs left join plans on tariffs.planID = plans.id".$plug.") as tbl_1 left join providers on tbl_1.providerID = providers.id";
				$f = $dbn->connect();
				$f = $f->prepare($sql);							
				$f->execute($binds);
				$row = $f->fetchAll();
				if($row){
					$data = json_encode($row, true);
				}else{
					$data = "[]";
				}
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				if($cardSerial != '-'){

				}else{
					$g = '{"error":{"message":"Search is required for secondary enrolee", "status":"1"}}';
				}
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});


$app->post('/api/hmo/sendMail', function(Request $req, Response $resp){
	try{
		$transBatch = "HMO/7865656/KIH";
		$pname = "Kyle Jay HealthCenter. Ibadan";
		$mail = new PHPMailer;
		$mail->IsSMTP();
		$mail->SMTPAuth = true;
		$mail->Host = "mail.healthtouch.me";		
		$mail->Username = "postmaster@healthtouch.me";
		$mail->Password = "aerotech1992-"; 
		$mail->setFrom('no-reply@healthtouch.me', 'Healthtouch Service');
		$mail->addAddress("elias.akin@gmail.com");
		$mail->addReplyTo('no-reply@healthtouch.me', 'some');	
		$mail->isHTML(true);
		//$mail->SMTPSecure = 'tls';
		$mail->Port = 25;                               // Set email format to HTML
		$message = "<p><strong>Dear HMO,</strong></p>
		<div>
		<div>You have new encounter entry, Batch Number: '<strong><span style=\"color: #ff6600;\">".$transBatch."</span></strong>' from <strong><span style=\"color: #333399;\">".$pname."</span></strong>. This email does not mean you have only one encounter as all encounter is summarized into a batch. Please login to view details.</div>
		<div><span style=\"color: #999999;\">Kindly ignore if you have already checked in.</span></div>
		</div>";
		$mail->Subject = 'Encounter Alert';
		$mail->Body = $message;	
		if(!$mail->send()) {
			echo "Something happened";
			echo "Mailer Error: " . $mail->ErrorInfo;
			//error_log('Mailer Error: ' . $mail->errorMessage());
			/*$app->flash("error", "We're having trouble with our mail servers at the moment.  Please try again later, or contact us directly by phone.");
			error_log('Mailer Error: ' . $mail->errorMessage());
			$app->halt(500);*/
		}else{
			echo "email sent";
		}  
	}catch(Exception $e){
		echo $e->getMessage();
	}
	/*$transBatch = "HMO/7865656/KIH";
	$pname = "Kyle Jay HealthCenter. Ibadan";
	$message = "<p><strong>Dear HMO,</strong></p>
	<div>
	<div>You have new encounter entry, Batch Number: '<strong><span style=\"color: #ff6600;\">".$transBatch."</span></strong>' from <strong><span style=\"color: #333399;\">".$pname."</span></strong>. This email does not mean you have only one encounter as all encounter is summarized into a batch. Please login to view details.</div>
	<div><span style=\"color: #999999;\">Kindly ignore if you have already checked in.</span></div>
	</div>";
	$dbn = new db();
	if($dbn->sendThis("elias.akin@gmail.com", $message)){
		echo "Yay!";
	}else{
		echo "Nay!";
	}*/
});
$app->get('/api/get/states', function(Request $req, Response $resp){
	$dbn = new db();
	$data = $dbn->selectFromQuery("select*from state");
	$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
	return $dbn->responseFormat($resp,$g);
});
$app->get('/api/get/lgs/{stateID}', function(Request $req, Response $resp){
	$stateID = $req->getAttribute('stateID');
	$dbn = new db();
	if(is_numeric($stateID)){
		$data = $dbn->selectFromQuery("select*from lgs where stateID = '$stateID'");
		$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
	}else{
		$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":[]}}';
	}
	return $dbn->responseFormat($resp,$g);
});


$app->get('/api/get/providers/{category}', function(Request $req, Response $resp){
	$headers = $req->getHeaders();
	$category = $req->getAttribute('category');
	$dbn = new db();
    if(true) {
        /*$publicKey  = $headers['HTTP_PUBLICKEY'][0];
		$username  = $headers['HTTP_USERNAME'][0];
		$hmo = $headers['HTTP_HMOID'][0];*/
		if(true){
			if(is_numeric($category) || $planID == "-"){
				if($category == "-"){
					$sql = "select*from providers";
				}else{
					$sql = "select * from providers where category = '$category'";
				}
				$data = $dbn->selectFromQuery($sql);
				$g = '{"error":{"message":"","status":"0"}, "success":{"message":"Data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
			}else{
				$g = '{"error":{"message":"The search term is invalid", "status":"1"}}';
			}
		}else{
			$g = '{"error":{"message":"The HMO Staff profile have not been found", "status":"1"}}';
		}
	}else{
		$g = '{"error":{"message":"All Profile fields are required", "status":"1"}}';
	}
	return $dbn->responseFormat($resp,$g);
});


$app->get('/api/get/service', function(Request $req, Response $resp){
	$stateID = $req->getAttribute('stateID');
	$dbn = new db();
	$data = $dbn->selectFromQuery("select*from serviceCategory");
	$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":'.$data.'}}';
	return $dbn->responseFormat($resp,$g);
});

$app->post('/api/upload/to/{id}', function(Request $req, Response $resp){
	$id = $req->getParam('id');
	$param = $req->getParam('body');
	$data = json_decode($param);
	$counter = 0;
	$data = $data->data;
	$dbn = new db();
	$data = json_decode($data);
	$Qin = $dbn->connect();
	while($counter < count($data)){
		$t = trim($data[$counter]);
		if(strlen($t) > 3){
			$sql = "insert into services (serviceName, serviceType) VALUES (:t, :id)";			
			$f = $Qin->prepare($sql);
			$f->bindParam(":t", $t);
			$f->bindParam(":id", $id);
			$f->execute();
		}		
		$counter++;
	}
	$g = '{"error":{"message":"","status":"0"}, "success":{"message":"data grabbed","code":"200"}, "content":{"data":"Total '.$counter.' was uploaded"}}';
	return $dbn->responseFormat($resp,$g);
});

$app->options('/{routes:.+}', function ($request, $response, $args) {
	$g = '{"error":{"message":"The Method may not have been implemented", "status":"1"}}';
	return $response
	->withStatus(200)
	->withHeader('Content-Type', 'application/json')
	->withHeader('Access-Control-Allow-Origin', '*')
	->withHeader('Access-Control-Allow-Headers', array('Content-Type', 'X-Requested-With', 'Authorization', 'username', 'someValue', 'someValue', 'publicKey'))
	->withHeader('Access-Control-Allow-Methods', array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'))
   	->write($g);
});
?>