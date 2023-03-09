<?php

namespace App\Entity;

class LoginCredentials
{
    protected $email = '';
    protected $password = '';

    public function getEmail() : string{
        return $this->email;
    }

    public function setEmail(string $email){
        $this->email = $email;
    }

    public function getPassword() : string{
        return $this->password;
    }

    public function setPassword(string $password){
        $this->password = $password;
    }
}

?>