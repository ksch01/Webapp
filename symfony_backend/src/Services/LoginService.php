<?php

namespace App\Services;

use App\Entity\User;
use App\Repository\UserRepository;

class LoginService{

    private UserRepository $repo;

    public function __construct(UserRepository $repo){
        $this->repo = $repo;
    }

    private function generateSessionKey(){
        $rand = random_bytes(4);
        return uniqid() . bin2hex($rand);
    }

    public function getVerifyUser(string $email, string $password){
        $user = $this->repo->find($email);
        

        if(!$user || !password_verify($password, $user->getPassword())){
            return false;
        }

        return $user;
    }

    public function startSession($user) : bool{
        if(!$user->getUsergroup()->isPrivLogin()){
            return false;
        }

        $user->setSession($this->generateSessionKey());
        $this->repo->flush();

        return true;
    }

    public function endSession($sessionKey) : bool{
        $user = $this->repo->findOneBy(["session" => $sessionKey]);
        
        if(!$user)return false;

        $user->setSession(null);
        $this->repo->flush();

        return true;
    }
}
?>