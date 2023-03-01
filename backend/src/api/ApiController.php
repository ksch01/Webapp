<?php
    require 'Controller.php';
    require __DIR__ . '/../model/Database.php';
    require 'Mailer.php';

    const PRIVILEGES_SUPER = "superuser";
    const PRIVILEGES_ADMIN = "admin";
    
    function formatPhone($phone){
        $formattedPhone = str_replace(' ', '', $phone);
        $formattedPhone = str_replace('-', '', $formattedPhone);
        if(mb_substr($formattedPhone, 0, 1) == '+')
            $formattedPhone = mb_substr($formattedPhone, 1);
        return $formattedPhone;
    }

    class ApiController extends Controller{
        
        function postLogin(){
            $this->requireParams(["email", "password"]);

            $user = getUserByEmail($this->body["email"]);
            if($user === false || !password_verify($this->body["password"], $user["password"])){
                http_response_code(401);     
                return;           
            }

            $priv = getPrivileges($user["group"]);
            if(!$priv["login"]){
                http_response_code(403);
                return;
            }

            $user["id"] = loginUser($this->body["email"]);

            unset($user["password"]);
            unset($priv["login"]);
            $user["privileges"] = $priv;
            
            header('content-type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode($user);
        }
        function deleteLogin(){
            if(!logoutUser($this->getParam("id"))){
                
                http_response_code(500);
                return;
            }
            
            http_response_code(200);
        }

        function getAccount(){
            if(empty($this->body)){

                $users = getUsers();
            }else{

                $this->requireOneOfParamsExclusive(["email", "name", "zip", "place", "phone"]);
                $users = searchUsers($this->body);
            }
            header('content-type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode($users);

        }

        function postAccount(){
            $this->requireParams(["email","name","zip","place","phone","password"]);

            #check if account already exists
            $user = getUserByEmail($this->body["email"]);
            if($user !== false){
                http_response_code(409);
                return;
            }

            $key = genKey();
            sendSignupMail($this->body["email"], $key);

            #save to database
            $this->body["password"] = password_hash($this->body["password"], PASSWORD_DEFAULT);
            $this->body["phone"] = formatPhone($this->body["phone"]);
            #TODO use saveUserId
            if(!saveUserId($this->body, $key)){
                http_response_code(500);
                echo "The account could not be registered to the database.";
                return;
            }

            http_response_code(201);
        }

        function putAccount(){
            $this->requireOneOfParams(["email", "name", "zip", "place", "phone", "password", "group"]);

            $invokerId = $this->getParam("id");
            $invoker = getUser($invokerId);
            if($invoker === false){
                http_response_code(401);
                return;
            }
            $priv = getPrivileges($invoker["group"]);

            if($this->hasParam("phone"))
                $this->body["phone"] = formatPhone($this->body["phone"]);

            $target;
            $discarded = false;
            if($this->hasParam("targetemail") && !($invoker["email"] === $this->body["targetemail"])){

                if($priv["edit_oth_cred"]){
                    if($priv["edit_oth_pass"]){
                        if($priv["edit_oth_priv"]){
                            $this->RequireOneOfParams(["email", "name", "zip", "place", "phone", "password", "group"]);
                        }else{
                            $this->RequireOneOfParams(["email", "name", "zip", "place", "phone", "password"]);
                            $discarded = $this->discardParams(["group"]);
                        }
                    }else{
                        $this->RequireOneOfParams(["email", "name", "zip", "place", "phone"]);
                        $discarded = $this->discardParams(["password", "group"]);
                    }
                }else{
                    http_response_code(403);
                    return;
                }

                $target = getUserByEmail($this->body["targetemail"]);
                if($target === false){
                    http_response_code(404);
                    return;
                }
            }else{

                if($priv["edit_own_cred"]){
                    if($priv["edit_own_pass"]){
                        if($priv["edit_own_priv"]){
                            $this->RequireOneOfParams(["email", "name", "zip", "place", "phone", "password", "group"]);
                        }else{
                            $this->RequireOneOfParams(["email", "name", "zip", "place", "phone", "password"]);
                            $discarded = $this->discardParams(["group"]);
                        }
                    }else{
                        $this->RequireOneOfParams(["email", "name", "zip", "place", "phone"]);
                        $discarded = $this->discardParams(["password", "group"]);
                    }
                }else{
                    http_response_code(403);
                    return;
                }
                $target = $invoker;
            }

            unset($this->body["id"]);
            $this->body["targetemail"] = $target["email"];
            if(updateUser($this->body)){
                if($discarded){
                    http_response_code(206);
                }else{
                    http_response_code(200);
                }
            }else{
                http_response_code(500);
            }
        }

        function deleteAccount(){
            $invoker = getUser($this->getParam("id"));
            if($invoker === false){
                http_response_code(401);
                return;
            }

            $priv = getPrivileges($invoker["group"]);
            if(!$priv["delete"]){
                http_response_code(403);
                return;
            }

            if(deleteUserByEmail($this->getParam("email"))){
                http_response_code(200);
            }else{
                http_response_code(500);
            }
        }

        function getVarify(){
            $invoker = getUser($this->getParam("key"));
            if($invoker === false){
                header("Location: " . frontendAddress . "/#/invalid");
                http_response_code(301);
                return;
            }

            $id = activateUser($this->getParam("key"));
            if($id === false){
                http_response_code(500);
                return;
            }

            header("Location: " . frontendAddress . "/#/activated");
            http_response_code(303);
        }
    }
?>