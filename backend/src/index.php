<?php
	include 'config.php';

	$configPath = 'config.json';
	$config = json_decode(file_get_contents($configPath));
	foreach($config as $key => $value){
		define($key, $value);
	}

	#allow cross origin
	if (isset($_SERVER['HTTP_ORIGIN'])){
		header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
		if($_SERVER['REQUEST_METHOD'] === "OPTIONS"){
			header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
			header("Access-Control-Allow-Headers: content-type");
			http_response_code(204);
			exit;
		}
	}

	function getControllerFunction(String $pathElement){

		$requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
		$pathElement = ucfirst(strtolower($pathElement));
		return $requestMethod . $pathElement;
	}

	$pathElements = explode('/', $_SERVER['REQUEST_URI']);

	if(!array_key_exists(3,$pathElements)){

		require "api/ApiController.php";
		$controller = new ApiController();

		if(array_key_exists(2,$pathElements)){
			$elements = explode('?', $pathElements[2]);
			$apiCall = getControllerFunction($elements[0]);
		}else
			$apiCall = strtolower($_SERVER['REQUEST_METHOD']);

		$controller->{$apiCall}();
	}else{
		
		http_response_code(404);
	}

	exit;
?>