<?php

namespace App\Entity\Form;

use Symfony\Component\Validator\Constraints as Assert;

class UserPrivileges{

    public const GROUPS = ['pending', 'user', 'superuser', 'admin'];

    #[Assert\NotBlank]
    #[Assert\Choice(choices: UserPrivileges::GROUPS)]
    protected $group;

    public function setGroup(string $group){
        $this->group = $group;
    }

    public function getGroup() : string {
        return $this->group;
    }
}

?>