<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

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

        $users = $repository->searchAllBy(["email" => $email, "name" => $name, "zip" => $zip, "place" => $place, "phone" => $phone]);
        foreach($users as $key=>$value){
            $users[$key] = new UserPrep($value);
        }

        return $this->json($users);
    }
}

?>