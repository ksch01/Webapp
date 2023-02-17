<?php
$servername = "localhost";
$username = "root";

$conn = new mysqli($servername, $username);

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
    `id` VARCHAR(17) PRIMARY KEY, 
    `email` VARCHAR(64) NOT NULL UNIQUE, 
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
    $id = $id;
    $email = $user["email"];
    $password = $user["password"];
    $name = $user["name"];
    $zip = $user["zip"];
    $place = $user["place"];
    $phone = $user["phone"];

    $sql = "INSERT INTO userdata (`id`, `email`, `password`, `name`, `zip`, `place`, `phone`, `privileges`)
    VALUES ('$id', '$email', '$password', '$name', '$zip', '$place', '$phone', '0')";

    if($conn->query($sql) === false){
        echo "Error saving user: " . $conn->error;
        return false;
    }
    return true;
}

function updateUser($user){
    global $conn;
    $id = $user["id"];
    $email = null;
    $password = null;
    $name = null;
    $zip = null;
    $place = null;
    $phone = null;
    $privileges = null;

    if(array_key_exists("email", $user))
        $email = $user["email"];
    if(array_key_exists("password", $user))
        $password = $user["password"];
    if(array_key_exists("name", $user))
        $name = $user["name"];
    if(array_key_exists("zip", $user))
        $zip = $user["zip"];
    if(array_key_exists("place", $user))
        $place = $user["place"];
    if(array_key_exists("phone", $user))
        $phone = $user["phone"];
    if(array_key_exists("privileges", $user))
        $privileges = $user["privileges"];

    $sql = "UPDATE userdata SET";
    if($email !== null)$sql = $sql . ", email='$email'";
    if($password !== null)$sql = $sql . ", password='$password'";
    if($name !== null)$sql = $sql . ", name='$name'";
    if($zip !== null)$sql = $sql . ", zip='$zip'";
    if($place !== null)$sql = $sql . ", place='$place'";
    if($phone !== null)$sql = $sql . ", phone='$phone'";
    if($privileges !== null)$sql = $sql . ", privileges='$privileges'";
    $sql = $sql . " WHERE id='$id'";

    $pos = strpos($sql,",");
    $sql = substr_replace($sql, ' ', $pos, 1);

    if($conn->query($sql) === false){
        echo "Error updating user: " . $conn->error;
        return false;
    }
    return true;
}

const KEY_RANDOM_DIGITS = 4;
function genKey(){
    $rand = random_bytes(ceil(KEY_RANDOM_DIGITS / 2));
    return uniqid() . bin2hex($rand);
}

function getUserByEmail($email){
    global $conn;
    $sql = "SELECT * FROM userdata WHERE email='$email'";
    $result = $conn->query($sql);

    if($result === false){

        echo "Error getting user: " . $conn->error;
        return false;
    }else{

        if($result->num_rows == 0){
            return false;
        }else{
            return $result->fetch_assoc();
        }
    }
}

function getUser($id){
    global $conn;
    $sql = "SELECT * FROM userdata WHERE id='$id'";
    $result = $conn->query($sql);

    if($result === false){

        echo "Error getting user: " . $conn->error;
        return false;
    }else{

        if($result->num_rows == 0){
            return false;
        }else{
            return $result->fetch_assoc();
        }
    }
}

function deleteUserByEmail($email){
    global $conn;
    $sql = "DELETE FROM userdata WHERE `email`='$email'";
    $result = $conn->query($sql);

    if($result === false){

        echo "Error deleting user: " . $conn->error;
        return false;
    }else{

        return true;
    }
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

function closeDatabaseConnection(){
    global $conn;
    $conn -> close();
}
?>