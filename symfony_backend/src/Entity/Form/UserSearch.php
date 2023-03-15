<?php

namespace App\Entity\Form;

use Symfony\Component\Validator\Constraints as Assert;

class UserSearch
{
    #[Assert\Regex('/^([[:alpha:]]*[[:blank:]]*@*\.*)*$/')]
    protected $email = '';

    #[Assert\Regex('/^([[:alpha:]]*[[:blank:]]*)*$/')]
    protected $name = '';

    #[Assert\Regex('/^[[:digit:]]{1,5}$/')]
    protected $zip = '';

    #[Assert\Regex('/^([[:alpha:]]*[[:blank:]]*)*$/')]
    protected $place = '';

    #[Assert\Regex('/^[[:digit:]]{1,12}$/')]
    protected $phone = '';

    public function getEmail() : string{
        return $this->email;
    }

    public function setEmail($email){
        if($email == null)$this->email = "";
        else $this->email = $email;
    }

    public function getName() : string{
        return $this->name;
    }

    public function setName($name){
        if($name == null)$this->name = "";
        else $this->name = $name;
    }

    public function getZip() : string{
        return $this->zip;
    }

    public function setZip($zip){
        if($zip == null)$this->zip = "";
        else $this->zip = $zip;
    }

    public function getPlace() : string{
        return $this->place;
    }

    public function setPlace($place){
        if($place == null)$this->place = "";
        else $this->place = $place;
    }

    public function getPhone() : string{
        return $this->phone;
    }

    public function setPhone($phone){
        if($phone == null)$this->phone = "";
        else $this->phone = $phone;
    }
}

?>