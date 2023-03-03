<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\User;
use App\Entity\UserPrep;
use App\Repository\UserRepository;

class UserController extends AbstractController{

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
}

?>