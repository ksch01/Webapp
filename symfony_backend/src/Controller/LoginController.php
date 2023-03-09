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

use App\Services\LoginService;

use Doctrine\Persistence\ManagerRegistry;


class LoginController extends AbstractController{
    
    private LoginService $service;

    function __construct(UserRepository $repo){
        $this->service = new LoginService($repo);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request) : Response{

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
        $user = $this->service->getVerifyUser($email, $password);
        if(!$user){
            throw new HttpException(401, 'incorrect email or password');
        }

        # --------------
        # update session
        # --------------
        $sessionStarted = $this->service->startSession($user);
        if(!$sessionStarted){
            throw new HttpException(403, 'not enough privileges');
        }

        # ---------------------
        # send response
        # ---------------------
        return $this->json($user);
    }

    #[Route('/login', name: 'api_logout', methods: ['DELETE'])]
    public function logout(Request $request) : Response{

        $sessionKey = $request->query->get('session');

        if($sessionKey === null){
            throw new BadRequestHttpException('logout requests require the id query parameter to be set');
        }

        $sessionEnded = $this->service->endSession($sessionKey);

        if(!$sessionEnded){
            throw new HttpException(401, 'invalid or incorrect session key');
        }

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }
}

?>