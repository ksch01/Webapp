<?php
class Controller
{
    protected $body;

    function __construct(){
        parse_str(file_get_contents('php://input'), $this->body);
    }

    public function __call($name, $arguments){

        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }

    protected function requireParams($params){
        foreach($params as $key) {
            if(!array_key_exists($key, $this->body)){
                http_response_code(400);
                exit;
            }
        }
    }

    protected function requireOneOfParams($params){
        foreach($params as $key) {
            if(array_key_exists($key, $this->body)){
                return;
            }
        }
        http_response_code(400);
        exit;
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