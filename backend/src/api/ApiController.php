<?php
    require 'Controller.php';
    require __DIR__ . '/../model/Database.php';

    const SIGNUP_MAIL_SUBJECT = "Ihre Regestrierung";
    function getSignupMailMessage($htmlEncodedKey){
        "
        <html>
            <head>
                <title>Ihre Regestrierung</title>
            </head>
            <body>
                Klicken Sie <a href=localhost/skygate/index.php/signup/?key=$htmlEncodedKey>hier</a> um Ihre Regestrierung abzuschließen
            </body>
        </html>s
        ";
    }

    function sendSingupMail($userData, $key){
        return mail($userData["email"], SIGNUP_MAIL_SUBJECT, getSignupMailMessage($key));
    }

    class ApiController extends Controller{
        
        function postLogin(){
            $this->requireParams(["email", "password"]);

            $user = getUserByEmail($this->body["email"]);
            if($user === false || !password_verify($this->body["password"], $user["password"])){
                
                http_response_code(401);     
                return;           
            }

            unset($user["password"]);
            
            header('content-type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode($user);
        }

        function getAccount(){
            $users = getUsers();
            header('content-type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode($users);
        }

        function postAccount(){
            $this->requireParams(["email","name","zip","place","phone","password"]);

            #TODO send signup mail and check for verification before adding user to database

            #check if account already exists
            if(getUserByEmail($this->body["email"]) !== false){
                http_response_code(409);
                return;
            }

            #save to database
            $this->body["password"] = password_hash($this->body["password"], PASSWORD_DEFAULT);
            if(!saveUser($this->body)){
                http_response_code(500);
                echo "The account could not be registered to the database.";
                return;
            }

            http_response_code(201);
        }

        function putAccount(){
            $this->requireParams(["id"]);
            $this->requireOneOfParams(["email", "name", "zip", "place", "phone", "password"]);

            $user = getUser($this->body["id"]);
            if($user === false){
                http_response_code(401);
                return;
            }

            if(array_key_exists("email", $this->body) && 
                $user["email"] != $this->body["email"] && 
                getUserByEmail($this->body["email"]) !== false){
                
                http_response_code(409);
                return;
            }
            
            if(array_key_exists("password", $this->body)){
                $this->body["password"] = password_hash($this->body["password"], PASSWORD_DEFAULT);
            }

            if(!updateUser($this->body)){
                http_response_code(500);
                echo "The account could not be updated on the database.";
                return;
            }

            http_response_code(200);
        }
    }
?>