<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class UserCredentials
{
    #[Assert\NotBlank]
    #[Assert\Email]
    protected $email = '';

    #[Assert\NotBlank]
    #[Assert\Regex('/^[[:upper:]][[:lower:]]*((\b\s[[:alpha:]][[:lower:]]*)*(\b\s[[:upper:]][[:lower:]]*))*$/')]
    protected $name = '';

    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 99999)]
    protected $zip;

    #[Assert\NotBlank]
    #[Assert\Regex('/^[[:upper:]][[:lower:]]*(\b\s[[:alpha:]][[:lower:]]*)*$/')]
    protected $place = '';

    #[Assert\NotBlank]
    #[Assert\Regex('/^[[:digit:]]{9,12}$/')]
    protected $phone = '';

    #[Assert\Length(min: 8)]
    protected $password = '';

    #[Assert\Expression('this.getRepeat() == this.getPassword()')]
    protected $repeat = '';

    public function getEmail() : string{
        return $this->email;
    }

    public function setEmail(string $email){
        $this->email = $email;
    }

    public function getName() : string{
        return $this->name;
    }

    public function setName(string $name){
        $this->name = $name;
    }

    public function getZip() : int{
        return $this->zip;
    }

    public function setZip(string $zip){
        $this->zip = $zip;
    }

    public function getPlace() : string{
        return $this->place;
    }

    public function setPlace(string $place){
        $this->place = $place;
    }

    public function getPhone() : string{
        return $this->phone;
    }

    public function setPhone(string $phone){
        $this->phone = $phone;
    }

    public function getPassword() : string{
        return $this->password;
    }

    public function setPassword(string $password){
        $this->password = $password;
    }

    public function getRepeat() : string{
        return $this->repeat;
    }

    public function setRepeat(string $repeat){
        $this->repeat = $repeat;
    }
}

?>