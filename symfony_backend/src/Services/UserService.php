<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\UserPrep;
use App\Repository\UserRepository;
use App\Repository\UserGroupRepository;

use App\Entity\Form\UserData;
use App\Entity\Form\UserCredentials;
use App\Entity\Form\UserPassword;
use App\Entity\Form\UserPrivileges;

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

    public function searchUsers($query, int $page=0, int $pageSize=0, string $sortCriteria='email', bool $sortAsc=true){
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

        $users = $this->sortUsers($users, $sortCriteria, $sortAsc);

        if($pageSize !== 0){
            $chunks = array_chunk($users, $pageSize, true);
            if(!array_key_exists($page, $chunks)){
                return ['result' => [], 'total' => sizeof($users)];
            }else{
                return ['result' => $chunks[$page], 'total' => sizeof($users)];
            }
        }

        return $users;
    }

    public function sortUsers(array $users, string $sortCriteria='email', bool $ascending=true) : array {

        $compare = null;

        if($sortCriteria === 'email'){
            $compare = function($userA, $userB){
                return strcasecmp($userA->getEmail(), $userB->getEmail());
            };
        }else if($sortCriteria === 'name'){
            $compare = function($userA, $userB){
                return strcasecmp($userA->getName(), $userB->getName());
            };
        }else if($sortCriteria === 'zip'){
            $compare = function($userA, $userB){
                return $userA->getZip() - $userB->getZip();
            };
        }else if($sortCriteria === 'place'){
            $compare = function($userA, $userB){
                return strcasecmp($userA->getPlace(), $userB->getPlace());
            };
        }else if($sortCriteria === 'phone'){
            $compare = function($userA, $userB){
                return strcasecmp($userA->getPhone(), $userB->getPhone());
            };
        }

        if($compare === null)throw new Exception("unknown sort criteria for sort users: \"" . $sortCriteria . "\"");

        usort($users, $compare);

        if(!$ascending)
            $users = array_reverse($users);

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

    public function updateUserWithData($session, $targetemail, UserData $user, ValidatorInterface $validator){
        $credentials = $user->getCredentials();

        return $this->updateUser($session, $targetemail, $credentials->getEmail(), $credentials->getName(), $credentials->getZip(), $credentials->getPlace(), $credentials->getPhone(), $user->getPasswordString(), $user->getUsergroupstring(), $validator);
    }
    public function updateUser($session, $targetemail, $email, $name, $zip, $place, $phone, $password, $group, ValidatorInterface $validator){
        
        $invoker = $this->repo->findOneBy(['session' => $session]);
        if(!$invoker)return false;

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

        if($email != null){
            if($target->getEmail() != $email){
                if($this->repo->find($email) != false){
                    return 'conflict';
                }
                $target->setEmail($email);
            }
        }

        if($name !== null){
            $target->setName($name);
        }

        if($zip !== null){
            $target->setZip($zip);
        }

        if($place !== null){
            $target->setPlace($place);
        }

        if($phone !== null){
            $target->setPhone($phone);
        }

        $errors = $validator->validate($target);
        if(count($errors) > 0){
            return 'invalid cred';
        }

        $discarded = false;

        if($privileges['edit_pass']){

            if($password !== null){
                $target->setPassword($password);
            }
        }else if($password !== null){

            $discarded = true;
        }

        if($privileges['edit_priv']){

            if($group !== null){
                $usergroup = $this->groupRepo->find($group);
                if(!$usergroup){
                    return 'invalid group';
                }
                $target->setUsergroup($usergroup);
            }
        }else if($group !== null){

            $discarded = true;
        }

        $this->repo->flush();

        if($discarded){
            return 'discarded';
        }else{
            return true;
        }
    }

    public function getUserPrivileges($email, $self=false){
        $user = $this->repo->find($email);
        if($user == null)return null;
        return $this->getPrivileges($user, $self);
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

    public function deleteUser($sessionKey, $targetemail){
        $invoker = $this->repo->findOneBy(["session" => $sessionKey]);
        if(!$invoker || !$invoker->getUsergroup()->isPrivDelete()){
            return 'missing privileges';
        }

        $target = $this->repo->find($targetemail);
        if(!$target){
            return'not found';
        }

        $this->repo->remove($target, true);
    }

    public function validateUser($activateKey){
        $user = $this->repo->findOneBy(["session" => $activateKey]);
        if(!$user || $user->getUsergroup()->getName() !== 'pending'){
            return false;
        }

        $usergroup = $this->groupRepo->find('user');
        $user->setUsergroup($usergroup);
        $user->setSession(null);

        $this->repo->flush();

        return true;
    }

    public function getUser($email){
        return $this->repo->find($email);
    }

    public function getUserBySession($sessionKey){
        return $this->repo->findOneBy(['session' => $sessionKey]);
    }

    public function getUsergroup($groupName){
        return $this->repo->find($groupName);
    }
}

?>