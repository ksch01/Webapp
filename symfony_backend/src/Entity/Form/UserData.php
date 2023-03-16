<?php

namespace App\Entity\Form;

use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Form\UserCredentials;
use App\Entity\Form\UserPassword;
use App\Entity\Form\UserPrivileges;

class UserData
{

    #[Assert\Valid]
    protected UserCredentials $credentials;
    #[Assert\Valid]
    protected UserPassword $password;
    #[Assert\Valid]
    protected UserPrivileges $usergroup;

    public function setUserdata($userdata){
        $this->credentials = $userdata->credentials;
        $this->password = $userdata->password;
        $this->usergroup = $userdata->usergroup;
    }

    public function getUserdata(){
        return $this;
    }

    public function setCredentials($credentials){
        $this->credentials = $credentials;
    }

    public function getCredentials(){
        return $this->credentials;
    }

    public function setPassword(UserPassword $password){
        $this->password = $password;
    }

    public function getPassword(){
        return $this->password;
    }

    public function getPasswordString(){
        return $this->password->getPassword();
    }

    public function setUsergroup(UserPrivileges $usergroup){
        $this->usergroup = $usergroup;
    }

    public function getUsergroup(){
        return $this->usergroup;
    }

    public function getUsergroupString(){
        return $this->usergroup->getGroup();
    }
}

?>