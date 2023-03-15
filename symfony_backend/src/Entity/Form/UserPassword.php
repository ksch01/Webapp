<?php

namespace App\Entity\Form;

use Symfony\Component\Validator\Constraints as Assert;

class UserPassword{
    #[Assert\Length(min: 8)]
    protected $password = '';

    #[Assert\Expression('this.getRepeat() == this.getPassword()')]
    protected $repeat = '';

    public function getPassword(){
        return $this->password;
    }

    public function setPassword($password){
        $this->password = $password;
    }

    public function getRepeat(){
        return $this->repeat;
    }

    public function setRepeat($repeat){
        $this->repeat = $repeat;
    }
}

?>