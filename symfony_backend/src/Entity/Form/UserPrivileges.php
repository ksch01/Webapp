<?php

namespace App\Entity\Form;

use Symfony\Component\Validator\Constraints as Assert;

class UserPrivileges{

    public const GROUPS = ['pending', 'user', 'superuser', 'admin', ''];
    public const GROUPS_KV = ['pending' => 'pending', 'user' => 'user', 'superuser' => 'superuser', 'admin' => 'admin'];

    #[Assert\Choice(choices: UserPrivileges::GROUPS, message: 'The value you selected ({{ value }}) is not a valid choice.')]
    protected $group;

    public function setGroup($group){
        $this->group = $group;
    }

    public function getGroup(){
        return $this->group;
    }
}

?>