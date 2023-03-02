<?php

namespace App\Controller;

# ---------------
# symfony imports
# ---------------
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

# ---------------
# persist imports
# ---------------
use App\Entity\User;
use App\Repository\UserRepository;

use Doctrine\Persistence\ManagerRegistry;


class LoginController extends AbstractController{

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(UserRepository $repository, Request $request) : Response{

        # -----------------------------------
        # get and validate request parameters
        # -----------------------------------
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        if($email === null || $password === null){
            throw new BadRequestHttpException('login requests require the email and password request parameter to be set');
        }

        # -----------------
        # authenticate user
        # -----------------
        $user = $repository->find($email);
        if(!$user || !password_verify($password, $user->getPassword())){
            throw new HttpException(401, 'incorrect email or password');
        }

        # --------------
        # update session
        # --------------
        $user->setSession($this->generateSessionKey());
        $repository->flush();

        # ---------------------
        # send response
        # ---------------------
        return $this->json(['user' => $user]);
    }

    private function generateSessionKey(){
        $rand = random_bytes(4);
        return uniqid() . bin2hex($rand);
    }

    #[Route('/login', name: 'api_logout', methods: ['DELETE'])]
    public function logout(UserRepository $repository, Request $request) : Response{

        $sessionKey = $request->query->get('session');

        if($sessionKey === null){
            throw new BadRequestHttpException('logout requests require the id query parameter to be set');
        }

        $user = $repository->findOneBy(["session" => $sessionKey]);

        if(!$user){
            throw new HttpException(401, 'invalid or incorrect session key');
        }

        $user->setSession(null);
        $repository->flush();

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }
}

?>