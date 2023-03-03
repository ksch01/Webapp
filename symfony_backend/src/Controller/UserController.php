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

        $password = password_hash($password, PASSWORD_DEFAULT);
        $user->setPassword($password);

        $repository->save($user, true);

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
            return $this->redirect('http://localhost:8080/#/invalid');
        }

        $usergroup = $groupRepository->find('user');
        $user->setUsergroup($usergroup);
        $user->setSession(null);

        $repository->flush();

        return $this->redirect('http://localhost:8080/#/activated');
    }
}

?>