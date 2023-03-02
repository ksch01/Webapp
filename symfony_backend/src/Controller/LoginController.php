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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

# ---------------
# persist imports
# ---------------
use App\Entity\User;

use Doctrine\Persistence\ManagerRegistry;


class LoginController extends AbstractController{

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(ManagerRegistry $doctrine, Request $request) : Response{

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
        $entityManager = $doctrine->getManager();
        $repository = $entityManager->getRepository(User::class);
        $user = $repository->find($email);

        if(!$user || !password_verify($password, $user->getPassword())){
            throw new UnauthorizedHttpException();
        }

        # --------------
        # update session
        # --------------
        $user->setSession($this->generateSessionKey());
        $entityManager->flush();

        # ---------------------
        # send response
        # ---------------------
        return $this->json(['user' => $user]);
    }

    #[Route('/login/{id}', name: 'api_logout', methods: ['DELETE'])]
    public function logout() : Response{

    }

    private function generateSessionKey(){
        $rand = random_bytes(4);
        return uniqid() . bin2hex($rand);
    }
}

?>