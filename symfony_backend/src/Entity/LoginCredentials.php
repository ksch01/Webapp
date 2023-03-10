<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class LoginCredentials
{
    #[Assert\NotBlank]
    #[Assert\Email]
    protected $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
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