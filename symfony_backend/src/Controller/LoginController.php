<?php

namespace App\Controller;

// ---------------
// symfony imports
// ---------------
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

// ---------------
// persist imports
// ---------------
use App\Entity\User;
use App\Entity\Form\LoginCredentials;
use App\Repository\UserRepository;

use App\Services\LoginService;

use Doctrine\Persistence\ManagerRegistry;


class LoginController extends AbstractController{
    
    private LoginService $service;

    function __construct(UserRepository $repo){
        $this->service = new LoginService($repo);
    }

    #[Route('/loginfromform', name: 'api_login_form', methods: ['GET', 'POST'])]
    public function loginFromForm(Request $request) : Response{

        $loginCredentials = new LoginCredentials();

        $form = $this->createFormBuilder($loginCredentials)
            ->add('email', TextType::class)
            ->add('password', PasswordType::class)
            ->add('submit', SubmitType::class, ['label' => 'Login'])
            ->getForm();

        $form->handleRequest($request);
        $error = false;
        if($form->isSubmitted() && $form->isValid()) {
            $loginCreds = $form->getData();

            $user = $this->service->getVerifyUser($loginCreds->getEmail(), $loginCreds->getPassword());

            if(!$user){
                $error = "The email or password provided were invalid.";
            }else if(!$this->service->startSession($user)){
                $error = "This account has not yet been activated. To activate this account use the link in your email.";
            }else{
                $session = $request->getSession();
                $session->start();

                $session->set('email', $user->getEmail());
                $session->set('name', $user->getName());
                $session->set('zip', $user->getZip());
                $session->set('place', $user->getPlace());
                $session->set('phone', $user->getPhone());
                $session->set('group', $user->getUsergroup()->getName());
                $session->set('sessionKey', $user->getSession());

                return $this->redirectToRoute('api_user_view');
            }
        }

        return $this->render('form.html.twig', [
            'form' => $form,
            'error' => $error,
            'pageTitle' => "Login"
        ]);
    }

    #[Route('/logout', name: 'api_logout_form', methods: ['GET'])]
    public function logoutFromForm(Request $request) : Response{
        $request->getSession()->clear();
        return $this->redirectToRoute('api_login_form');
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request) : Response{

        // -----------------------------------
        // get request parameters
        // -----------------------------------

        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // -----------------------------------
        // validate request parameters
        // -----------------------------------

        if($email === null || $password === null){
            throw new BadRequestHttpException('login requests require the email and password request parameter to be set');
        }

        // -----------------
        // authenticate user
        // -----------------

        $user = $this->service->getVerifyUser($email, $password);
        if(!$user){
            throw new HttpException(401, 'incorrect email or password');
        }

        // --------------
        // update session
        // --------------

        if(!$this->service->startSession($user)) throw new HttpException(403, 'not enough privileges');

        // ---------------------
        // send response
        // ---------------------
        return $this->json($user);
    }

    #[Route('/login', name: 'api_logout', methods: ['DELETE'])]
    public function logout(Request $request) : Response{

        $sessionKey = $request->query->get('session');

        if($sessionKey === null){
            throw new BadRequestHttpException('logout requests require the id query parameter to be set');
        }

        if(!$this->service->endSession($sessionKey)) throw new HttpException(401, 'invalid or incorrect session key');

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }
}

?>