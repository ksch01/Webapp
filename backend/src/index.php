<?php
	#test
	#allow cross origin for development
	header("Access-Control-Allow-Origin: http://localhost:5173");
	if($_SERVER['REQUEST_METHOD'] === "OPTIONS"){
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
		header("Access-Control-Allow-Headers: content-type");
	}

	include 'config.php';

	function getControllerFunction(String $pathElement){

		$requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
		$pathElement = ucfirst(strtolower($pathElement));
		return $requestMethod . $pathElement;
	}

	$pathElements = explode('/', $_SERVER['REQUEST_URI']);

	if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
		http_response_code(204);
		exit;
	}

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