<?php

namespace App\Entity;

class UserPrep{

    private ?string $email;
    private ?string $name;
    private ?int $zip;
    private ?string $place;
    private ?string $phone;
    private ?UserGroupPrep $usergroup;

    function __construct(?User $user){
        $this->email = $user->getEmail();
        $this->name = $user->getName();
        $this->zip = $user->getZip();
        $this->place = $user->getPlace();
        $this->phone = $user->getPhone();
        $this->usergroup = new UserGroupPrep($user->getUserGroup()->getName());
    }

    function getEmail() : ?string
    {
        return $this->email;
    }

    function getName() : ?string
    {
        return $this->name;
    }

    function getZip() : ?int
    {
        return $this->zip;
    }

    function getPlace() : ?string
    {
        return $this->place;
    }

    function getPhone() : ?string
    {
        return $this->phone;
    }

    function getUsergroup() : ?UserGroupPrep
    {
        return $this->usergroup;
    }
}

class UserGroupPrep{

    private ?string $name;

    function __construct(?string $name){
        $this->name = $name;
    }

    function getName() : ?string
    {
        return $this->name;
    }
}

?>