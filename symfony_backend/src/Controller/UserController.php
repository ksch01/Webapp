<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\User;
use App\Entity\UserPrep;
use App\Repository\UserRepository;
use App\Repository\UserGroupRepository;

class UserController extends AbstractController{
    
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

    #[Route('/user', name:'api_user_post', methods:['POST'])]
    public function addUser(UserRepository $repository, UserGroupRepository $groupRepository, ValidatorInterface $validator, Request $request) : Response{

        $email = $request->request->get('email');
        if($repository->find($email) != false){
            throw new ConflictHttpException('user with email "' . $email . '" does already exist');
        }

        $password = $request->request->get('password');
        if($password === null || strlen($password) < 8){
            throw new BadRequestHttpException('post user request require the password request parameter to be set and have a length of at least 8');
        }

        $user = new User();
        $user->setEmail($request->request->get('email'));
        $user->setName($request->request->get('name'));
        $user->setZip($request->request->get('zip'));
        $user->setPlace($request->request->get('place'));
        $user->setPhone($request->request->get('phone'));

        $errors = $validator->validate($user);
        if(count($errors) > 0){
            $errorString = (string) $errors;
            throw new BadRequestHttpException('post user request require the email, name, zip, place and phone request parameters to be set and have valid values');
        }
        
        $user->setSession($this->generateSessionKey());

        $usergroup = $groupRepository->find('pending');
        $user->setUsergroup($usergroup);

        $password = password_hash($password, PASSWORD_DEFAULT);
        $user->setPassword($password);

        $repository->save($user, true);

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }
}

?>