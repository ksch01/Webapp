<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(mysqlAddress, mysqlUsername, mysqlPassword);

if($conn->connect_errno){
    die("Database connection failed: " . §conn->connect_error);
    exit -1;
}

$sql = "CREATE DATABASE IF NOT EXISTS skygateDB";
if ($conn -> query($sql) === true){
    $conn -> query("USE skygateDB");
}else {
    echo "Error creating database: " . $conn->error;
    exit -1;
}

$sql = "CREATE TABLE IF NOT EXISTS `userdata` (
    `email` VARCHAR(64) PRIMARY KEY , 
    `id` VARCHAR(21) UNIQUE , 
    `password` VARCHAR(60) NOT NULL , 
    `name` VARCHAR(64) NOT NULL , 
    `zip` MEDIUMINT UNSIGNED NOT NULL , 
    `place` VARCHAR(64) NOT NULL , 
    `phone` VARCHAR(15) NOT NULL , 
    `privileges` TINYINT UNSIGNED NOT NULL
);";
if($conn -> query($sql) === false){
    echo "Error creating table: " . $conn->error;
    exit -2;
}

function saveUser($user){
    return saveUserId($user, genKey());
}
function saveUserId($user, $id){
    global $conn;

    $query = $conn->prepare("INSERT INTO userdata (`id`, `email`, `password`, `name`, `zip`, `place`, `phone`, `privileges`)
    VALUES (?, ?, ?, ?, ?, ?, ?, '0')");
    $query->bind_param("ssssisi", 
        $id, 
        $user["email"], 
        $user["password"], 
        $user["name"], 
        $user["zip"], 
        $user["place"], 
        $user["phone"]);

    if($query->execute() === false){
        echo "Error saving user: " . $conn->error;
        return false;
    }
    return true;
}

function updateUser($user){
    global $conn;

    $targetemail = &$user["targetemail"];
    unset($user["targetemail"]);

    $sqlParams = "";
    $paramTypes = "";
    $params = [&$paramTypes];

    $index = 1;

    foreach($user as $key => $value){
        if($key === "zip" || $key === "phone" || $key === "privileges"){
            $paramType = "i";
        }else{
            $paramType = "s";
        }
        $paramTypes = $paramTypes . $paramType;

        $params[$index] = &$value;

        if(!empty($sqlParams)){
            $sqlParams = $sqlParams . ", ";
        }
        $sqlParams = $sqlParams . $key . "=?";

        $index++;
    }

    $sql = "UPDATE userdata SET " . $sqlParams . " WHERE email=?";
    $paramTypes = $paramTypes . "s";
    $params[$index] = &$targetemail;

    $query = $conn->prepare($sql);
    call_user_func_array([$query, "bind_param"], $params);

    if($query->execute() === false){
        echo "Error updating user: " . $conn->error;
        return false;
    }
    return true;
}

function getUserByEmail($email){
    global $conn;

    $query = $conn->prepare("SELECT * FROM userdata WHERE email=?");
    $query->bind_param("s", $email);

    if($query->execute() === false){
        echo "Error getting user: " . $conn->error;
        return false;
    }

    $result = $query->get_result();

    if($result->num_rows == 0){
        return false;
    }else{
        return $result->fetch_assoc();
    }
}
function loginUser($email){
    global $conn;

    $sessionId = genKey();
    $query = $conn->prepare("UPDATE userdata SET `id`=? WHERE `email`=?");
    $query->bind_param("ss", $sessionId, $email);

    if($query->execute() === false){
        echo "Error updating session: " . $conn->error;
        return false;
    }

    return $sessionId;
}
function logoutUser($id){
    global $conn;

    $query = $conn->prepare("UPDATE userdata SET `id`=null WHERE `id`=?");
    $query->bind_param("s", $id);

    if($query->execute() === false){
        echo "Error closing session: " . $conn->error;
        return false;
    }
    return true;
}

function getUser($id){
    global $conn;

    $query = $conn->prepare("SELECT * FROM userdata WHERE `id`=?");
    $query->bind_param("s", $id);

    if($query->execute() === false){
        echo "Error getting user: " . $conn->error;
        return false;
    }

    $result = $query->get_result();
    if($result->num_rows == 0){
        return false;
    }else{
        return $result->fetch_assoc();
    }
}

function activateUser($key){
    global $conn;

    $query = $conn->prepare("UPDATE userdata SET `id`=null, `privileges`='1' WHERE `id`=?");
    $query->bind_param("s", $key);

    if($query->execute() === false){
        echo "Error activating user: " . $conn->error;
        return false;
    }
    return $id;
}

function deleteUserByEmail($email){
    global $conn;

    $query = $conn->prepare("DELETE FROM userdata WHERE `email`=?");
    $query->bind_param("s", $email);

    if($query->execute() === false){
        echo "Error deleting user: " . $conn->error;
        return false;
    }
    return true;
}

function getUsers(){
    global $conn;

    $sql = "SELECT `email`, `name`, `zip`, `place`, `phone`, `privileges` FROM userdata";
    $result = $conn->query($sql);

    if($result === false){

        echo "Error getting users: " . $conn->error;
        return false;
    }else{

        $users = [];
        while($row = $result->fetch_assoc()) {
            array_push($users, $row);
        }
        return $users;
    }
}

function searchUsers($query){
    global $conn;

    $sqlParams = "";
    $paramTypes = "";
    $params = [&$paramTypes];

    $index = 1;

    foreach($query as $key => $value){
        if($key === "zip" || $key === "phone" || $key === "privileges"){
            $paramType = "i";
        }else{
            $paramType = "s";
        }
        $paramTypes = $paramTypes . $paramType;

        $value = "%" . $value . "%";
        $params[$index] = &$value;

        if(!empty($sqlParams)){
            $sqlParams = $sqlParams . " AND ";
        }
        $sqlParams = $sqlParams . $key . " LIKE ?";

        $index++;
    }

    $sql = "SELECT `email`, `name`, `zip`, `place`, `phone`, `privileges` FROM userdata WHERE " . $sqlParams;

    $query = $conn->prepare($sql);
    call_user_func_array([$query, "bind_param"], $params);

    if($query->execute() === false){
        echo "Error searching users: " . $conn->error;
        return false;
    }

    $result = $query->get_result();

    $users = [];
    while($row = $result->fetch_assoc()){
        array_push($users, $row);
    }
    return $users;
}

const KEY_RANDOM_DIGITS = 8;
function genKey(){
    $rand = random_bytes(ceil(KEY_RANDOM_DIGITS / 2));
    return uniqid() . bin2hex($rand);
}

function closeDatabaseConnection(){
    global $conn;
    $conn -> close();
}
?>