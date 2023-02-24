<?php

class Controller
{
    protected $body;

    function __construct(){
        parse_str(file_get_contents('php://input'), $this->body);
        foreach($_GET as $param => $value){
            $value = trim($value);
            $this->body[$param] = $value;
        }      
    }

    public function __call($name, $arguments){

        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }

    protected function requireParams($params, $statusOnFail = 400){
        foreach($params as $key) {
            if(!array_key_exists($key, $this->body)){
                echo("Param " . $key . " was required but didn't exist.");
                http_response_code($statusOnFail);
                exit;
            }
        }
    }

    protected function requireOneOfParams($params, $statusOnFail = 400){
        foreach($params as $key) {
            if(array_key_exists($key, $this->body)){
                return;
            }
        }
        http_response_code($statusOnFail);
        exit;
    }
    protected function requireOneOfParamsExclusive($params, $statusOnFail = 400){
        $keyFound = false;
        $hasOne = false;
        foreach($this->body as $key => $value){
            foreach($params as $paramKey){
                if($key == $paramKey){
                    $keyFound = true;
                    break;
                }
            }
            if($keyFound){
                $hasOne = true;
            }else{
                http_response_code($statusOnFail);
                exit;
            }
        }
        if(!$hasOne){
            http_response_code($statusOnFail);
            exit;
        }
    }

    protected function hasParam($param){
        return array_key_exists($param, $this->body);
    }

    protected function getParam($param, $statusOnFail = 400){
        if($this->hasParam($param)){
            return $this->body[$param];
        }else{
            echo("Param " . $param . "was required but didn't exist.");
            http_response_code($statusOnFail);
            exit;
        }
    }

    protected function discardParams($params){
        $discarded = false;
        foreach($params as $param){
            if($this->hasParam($param)){
                unset($this->body[$param]);
                $discarded = true;
            }
        }
        return $discarded;
    }
    
    protected function sendOutput($data, $httpHeaders=array()){

        header_remove('Set-Cookie');
        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }
        echo $data;
        exit;
    }
}
?>