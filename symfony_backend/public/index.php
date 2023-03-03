<?php

if (isset($_SERVER['HTTP_ORIGIN'])){
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    if($_SERVER['REQUEST_METHOD'] === "OPTIONS"){
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
        header("Access-Control-Allow-Headers: content-type");
        http_response_code(204);
        exit;
    }
}

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
