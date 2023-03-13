<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\UserPrep;
use App\Repository\UserRepository;
use APP\Repository\UserGroupRepository;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserService{

    private UserRepository $repo;
    private UserGroupRepository $groupRepo;
    private UrlGeneratorInterface $router;

    public function __construct(UserRepository $repo, UserGroupRepository $groupRepo, UrlGeneratorInterface $router){
        $this->repo = $repo;
        $this->groupRepo = $groupRepo;
        $this->router = $router;
    }

    private function generateSessionKey(){
        $rand = random_bytes(4);
        return uniqid() . bin2hex($rand);
    }

    public function searchUsers($query){
        $email = $query->get('email', '');
        $name = $query->get('name', '');
        $zip = $query->get('zip', '');
        $place = $query->get('place', '');
        $phone = $query->get('phone', '');

        $users = $this->repo->searchAllBy([
            "email" => $email, 
            "name" => $name, 
            "zip" => $zip, 
            "place" => $place, 
            "phone" => $phone]);

        foreach($users as $key=>$value){
            $users[$key] = new UserPrep($value);
        }

        return $users;
    }

    public function doesUserExist($email) : bool {
        return $this->repo->find($email) != false; 
    }

    public function getValidateUser($email, $name, $zip, $place, $phone, $password, ValidatorInterface $validator){
        
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setZip($zip);
        $user->setPlace($place);
        $user->setPhone($phone);
        $user->setPassword($password);

        $errors = $validator->validate($user);
        if(count($errors) > 0){
            return false;
        }

        return $user;
    }

    public function signupUser($user, MailerInterface $mailer){

        $activateKey = $this->generateSessionKey();
        $user->setSession($activateKey);

        $link = $this->router->generate('api_user_varify', ['key' => $activateKey], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('ks0122021@gmail.com')
            ->to($user->getEmail())
            ->subject('Ihre Regestrierung')
            ->html('<h1>Ihre Regestrierung</h1>Klicken Sie <a href=' . $link . '>hier</a> um Ihre Regestrierung abzuschließen');
        $mailer->send($email);

        $usergroup = $this->groupRepo->find('pending');
        $user->setUsergroup($usergroup);

        $this->repo->save($user, true);
    }

    public function updateUser($request, ValidatorInterface $validator){
        
        $invoker = $this->repo->findOneBy(['session' => $request->get('session')]);
        if(!$invoker)return false;

        $targetemail = $request->get('target');
        if($targetemail == null || $targetemail = $invoker->getEmail()){
            
            $target = $invoker;
            $privileges = $this->getPrivileges($invoker, true);
        }else{

            $target = $this->repo->find($targetemail);
            if(!$target){
                return 'not found';
            }
            $privileges = $this->getPrivileges($invoker, false);
        }

        if(!$privileges['edit_cred']){
            return 'missing privileges';
        }

        $email = $request->get('email');
        if($email != null){
            if($target->getEmail() != $email){
                if($repository->find($email) != false){
                    return 'conflict';
                }
                $target->setEmail($email);
            }
        }

        $name = $request->get('name');
        if($name !== null){
            $target->setName($name);
        }

        $zip = $request->get('zip');
        if($zip !== null){
            $target->setZip($zip);
        }

        $place = $request->get('palce');
        if($place !== null){
            $target->setPlace($place);
        }

        $phone = $request->get('phone');
        if($phone !== null){
            $target->setPhone($phone);
        }

        $errors = $validator->validate($target);
        if(count($errors) > 0){
            return 'invalid cred';
        }

        $discarded = false;

        if($privileges['edit_pass']){

            $password = $request->get('password');
            if($password !== null){
                $target->setPassword($password);
            }
        }else if($request->request->get('password') !== null){

            $discarded = true;
        }

        if($privileges['edit_priv']){

            $group = $request->get('group');
            if($group !== null){
                $usergroup = $this->groupRepo->find($group);
                if(!$usergroup){
                    return 'invalid group';
                }
                $target->setUsergroup($usergroup);
            }
        }else if($request->request->get('group') !== null){

            $discarded = true;
        }

        $this->repo->flush();

        if($discarded){
            return 'discarded';
        }else{
            return true;
        }
    }

    private function getPrivileges(User $user, $self =false){
        if($self){
            return [
                'edit_cred' => $user->getUsergroup()->isEditOwnCred(),
                'edit_pass' => $user->getUsergroup()->isEditOwnPass(),
                'edit_priv' => $user->getUsergroup()->isEditOwnPriv()
            ];
        }else{
            return [
                'edit_cred' => $user->getUsergroup()->isEditOthCred(),
                'edit_pass' => $user->getUsergroup()->isEditOthPass(),
                'edit_priv' => $user->getUsergroup()->isEditOthPriv()
            ];
        }
    }

    public function deleteUser($sessionKey, $targetEmail){
        $invoker = $this->repo->findOneBy(["session" => $sessionKey]);
        if(!$invoker || !$invoker->getUsergroup()->isPrivDelete()){
            return 'missing privileges';
        }

        $target = $this->repo->find($targetemail);
        if(!$target){
            return'not found';
        }

        $repository->remove($target, true);
    }

    public function validateUser($activateKey){
        $user = $this->repo->findOneBy(["session" => $activateKey]);
        if(!$user || $user->getUsergroup()->getName() !== 'pending'){
            return false;
        }

        $usergroup = $this->groupRepo->find('user');
        $user->setUsergroup($usergroup);
        $user->setSession(null);

        $repository->flush();

        return true;
    }

    public function getUsergroup($groupName){
        return $this->repo->find($groupName);
    }
}

?>