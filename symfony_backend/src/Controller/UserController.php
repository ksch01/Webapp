<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\User;
use App\Entity\UserPrep;
use App\Repository\UserRepository;
use App\Repository\UserGroupRepository;

class UserController extends AbstractController{

    private const FRONTEND_ADDRESS = 'localhost';
    private const FRONTEND_PORT = '8080';
    
    private function generateSessionKey(){
        $rand = random_bytes(4);
        return uniqid() . bin2hex($rand);
    }

    #[Route('/user', name:'api_user_get', methods:['GET'])]
    public function searchUsers(UserRepository $repository, Request $request) : Response{

        $email = $request->query->get('email', '');
        $name = $request->query->get('name', '');
        $zip = $request->query->get('zip', '');
        $place = $request->query->get('place', '');
        $phone = $request->query->get('phone', '');

        $users = $repository->searchAllBy([
            "email" => $email, 
            "name" => $name, 
            "zip" => $zip, 
            "place" => $place, 
            "phone" => $phone]);

        foreach($users as $key=>$value){
            $users[$key] = new UserPrep($value);
        }
        return $this->json($users);
    }

    #[Route('/user', name:'api_user_post', methods:['POST'])]
    public function addUser(UserRepository $repository, UserGroupRepository $groupRepository, ValidatorInterface $validator, MailerInterface $mailer,Request $request) : Response{

        $emailAddress = $request->request->get('email');
        if($repository->find($emailAddress) != false){
            throw new ConflictHttpException('user with email "' . $emailAddress . '" does already exist');
        }

        $password = $request->request->get('password');
        if($password === null || strlen($password) < 8){
            throw new BadRequestHttpException('post user requests require the password request parameter to be set and have a length of at least 8');
        }

        $user = new User();
        $user->setEmail($emailAddress);
        $user->setName($request->request->get('name'));
        $user->setZip($request->request->get('zip'));
        $user->setPlace($request->request->get('place'));
        $user->setPhone($request->request->get('phone'));

        $errors = $validator->validate($user);
        if(count($errors) > 0){
            throw new BadRequestHttpException('post user requests require the email, name, zip, place and phone request parameters to be set and have valid values');
        }
        
        $activateKey = $this->generateSessionKey();
        $user->setSession($activateKey);

        $link = $this->generateUrl('api_user_varify', ['key' => $activateKey], UrlGeneratorInterface::ABSOLUTE_URL);
        $email = (new Email())
            ->from('ks0122021@gmail.com')
            ->to($emailAddress)
            ->subject('Ihre Regestrierung')
            ->html('<h1>Ihre Regestrierung</h1>Klicken Sie <a href=' . $link . '>hier</a> um Ihre Regestrierung abzuschließen');
        $mailer->send($email);

        $usergroup = $groupRepository->find('pending');
        $user->setUsergroup($usergroup);

        $user->setPassword($password);

        $repository->save($user, true);

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/user', name:'api_user_update', methods:['PUT'])]
    public function updateUser(UserRepository $repository, UserGroupRepository $groupRepository, ValidatorInterface $validator, Request $request) : Response{

        $sessionKey = $request->request->get('session');
        if($sessionKey === null){
            throw new BadRequestHttpException('update user requests require the session request parameter to be set');
        }

        $invoker = $repository->findOneBy(["session" => $sessionKey]);
        if(!$invoker){
            throw new HttpException(403, 'not enough privileges');
        }

        $targetemail  = $request->request->get('target');
        if($targetemail === null || $targetemail == $invoker->getEmail()){

            $target = $invoker;
            $privileges = $this->getPrivileges($invoker, true);
        }else{

            $target = $repository->find($targetemail);
            if(!$target){
                throw new NotFoundHttpException('update user target "' . $targetemail . '" could not be found');
            }
            $privileges = $this->getPrivileges($invoker, false);
        }

        if(!$privileges['edit_cred']){
            throw new HttpException(403, 'not enough privileges');
        }

        $email = $request->request->get('email');
        if($email !== null){
            if($target->getEmail() != $email){
                if($repository->find($email) != false){
                    throw new ConflictHttpException('another user with email "' . $email . '" does already exist');
                }
                $target->setEmail($email);
            }
        }

        $name = $request->request->get('name');
        if($name !== null){
            $target->setName($name);
        }

        $zip = $request->request->get('zip');
        if($zip !== null){
            $target->setZip($zip);
        }

        $place = $request->request->get('palce');
        if($place !== null){
            $target->setPlace($place);
        }

        $phone = $request->request->get('phone');
        if($phone !== null){
            $target->setPhone($phone);
        }

        $errors = $validator->validate($target);
        if(count($errors) > 0){
            throw new BadRequestHttpException('update user requests require a valid email, name, zip, place and phone request parameter if set');
        }

        $discarded = false;

        if($privileges['edit_pass']){

            $password = $request->request->get('password');
            if($password !== null){
                $target->setPassword($password);
            }
        }else if($request->request->get('password') !== null){

            $discarded = true;
        }

        if($privileges['edit_priv']){

            $group = $request->request->get('group');
            if($group !== null){
                $usergroup = $groupRepository->find($group);
                if(!$usergroup){
                    throw new BadRequestHttpException('update user requests require a valid group request parameter if set');
                }
                $target->setUsergroup($usergroup);
            }
        }else if($request->request->get('group') !== null){

            $discarded = true;
        }

        $repository->flush();

        $response = new Response();
        if($discarded){
            $response->setStatusCode(206);
        }else{
            $response->setStatusCode(200);
        }
        return $response;
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
    
    #[Route('/user', name:'api_user_delete', methods:['DELETE'])]
    public function deleteUser(UserRepository $repository, Request $request) : Response{

        $sessionKey = $request->query->get('session');
        $targetemail = $request->query->get('target');
    
        if($sessionKey === null || $targetemail === null){
            throw new BadRequestHttpException('delete user requests require the session and target query parameter to be set');
        }

        $invoker = $repository->findOneBy(["session" => $sessionKey]);
        if(!$invoker || !$invoker->getUsergroup()->isPrivDelete()){
            throw new HttpException(403, 'not enough privileges');
        }

        $target = $repository->find($targetemail);
        if(!$target){
            throw new NotFoundHttpException('delete user target "' . $targetemail . '" could not be found');
        }

        $repository->remove($target, true);

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/varify', name:'api_user_varify', methods:['GET'])]
    public function validateUser(UserRepository $repository, UserGroupRepository $groupRepository, Request $request) : Response{
        
        $activateKey = $request->query->get('key');
        if($activateKey === null){
            throw new BadRequestHttpException('varify requests require the key query parameter to be set');
        }

        $user = $repository->findOneBy(["session" => $activateKey]);
        if(!$user || $user->getUsergroup()->getName() !== 'pending'){
            return $this->redirect($this->getAbsoluteFrontendAddress('/#/invalid'));
        }

        $usergroup = $groupRepository->find('user');
        $user->setUsergroup($usergroup);
        $user->setSession(null);

        $repository->flush();

        return $this->redirect($this->getAbsoluteFrontendAddress('/#/activated'));
    }

    private function getAbsoluteFrontendAddress($relative){
        return 'http://' . self::FRONTEND_ADDRESS . ':' . self::FRONTEND_PORT . $relative;
    }

    #[Route('/usergroup', name:'api_get_usergroup', methods:['GET'])]
    public function getUsergroup(UsergroupRepository $repository, Request $request) : Response{

        $groupname = $request->query->get('group');
        if($groupname === null){
            throw new BadRequestHttpException('get usergroup requests require the group query parameter to be set');
        }

        $usergroup = $repository->find($groupname);
        if(!$usergroup){
            return new NotFoundHttpException('get usergroup target ' . $groupname . ' could not be found');
        }

        return $this->json($usergroup);
    }
}

?>