<?php
error_reporting();
class db{
	/*
		Config
	*/
	private $dbhost = '127.0.0.1';
	private $dbuser = 'root';
	private $dbpass = '';
	private $dbname = 'healthTouch';
	/*
		Connection
	*/
	public function connect(){
		$dbh = new PDO("mysql:host=$this->dbhost;dbname=$this->dbname", $this->dbuser, $this->dbpass);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}

	public function jsonFormat($json){
		if(is_string($json)){
			$json = (urldecode($json) != null)? urldecode($json) : $json;
			$json = $this->cleanJson($json);
		}else{
			$json = json_encode($json);
		}
		if($this->isJson($json)){
			return json_decode($json);
		}else{
			return NULL;
		}
	}
	function isJson($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}
	function responseFormat($resp, $obj){
		return $resp->withStatus(200)
		->withHeader('Content-Type', 'application/json')
	  	->withHeader('Access-Control-Allow-Origin', '*')
	 	->withHeader('Access-Control-Allow-Headers', array('Content-Type', 'X-Requested-With', 'Authorization'))
	  	->withHeader('Access-Control-Allow-Methods', array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'))
		->write($obj);
	}
	function cleanException($ex){
		$poison = array("'", "\\", "\"");
		$exf = str_replace($poison, "", $ex);
		return $exf;
	}
	function cleanJson($json){
		$start = array();
		$endIndex = array();
		$count = 0;
		$json = trim($json);
		while($count < strlen($json)){
			if(substr($json,$count, 1) == "{"){
				array_push($start,$count);
			}else if(substr($json,$count, 1) == "}"){
				array_push($endIndex,$count);
			}else{
			}
			$count++;
		}
		return substr($json, $start[0], $endIndex[count($endIndex)-1] - $start[0] + 1);
	}

	function isExist($query){
		$dbn = $this->connect();
		$q = $dbn->query($query);
		if($q->fetchColumn() > 0){
			return true;
		}else{
			return false;
		}
	}
	function hmoStaffExist($username, $publicKey, $hmoID){
		return $this->isExist("select*from hmostaffs where username = '$username' and publicKey = '$publicKey' and hmoID = '$hmoID'");		
	}
	function selectFromQuery($query){
		$db = $this->connect();
		$f = $db->prepare($query);
		$f->execute();
		$row = $f->fetchAll();
		if($row){
			$data = json_encode($row, true);
		}else{
			$data = "[]";
		}
		return $data;
	}
	function sendThis($to, $message){
		$header = "From: BeepXchangePlus <support@beepxchangeplus.com>\r\n"; 
			$header .= "To: ".$to." \r\n"; 
			$header.= "MIME-Version: 1.0\r\n"; 
			$header.= "Content-Type: text/html; charset=ISO-8859-1\r\n"; 
			$header.= "X-Priority: 1\r\n";
			if(is_array($message)){
				$body = $message[0];
				$subject = $message[1];
			}else{
				$subject = "BeepXchangePlus Transaction Notice";
				$body = $message;
			}
			if(mail($to,$subject,$body,$header)){
				return true;
			}else{
				return false;
			}
	}
}
?>