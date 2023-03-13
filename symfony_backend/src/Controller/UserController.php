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

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\User;
use App\Entity\UserCredentials;
use App\Repository\UserRepository;
use App\Repository\UserGroupRepository;
use App\Services\UserService;

class UserController extends AbstractController{

    private $menuPoints;

    private string $frontendAddress;
    private string $frontendPort;

    private UserService $service;

    function __construct(string $frontendAddress, string $frontendPort, UserRepository $userRepo, UserGroupRepository $userGroupRepo, UrlGeneratorInterface $router){
        $this->frontendAddress = $frontendAddress;
        $this->frontendPort = $frontendPort;

        $this->menuPoints = [
            '/user/view' => "My Data",
            '/user/list' => "List Users",
            '/user/search' => "Search",
            '/logout' => "Logout"
        ];

        $this->service = new UserService($userRepo, $userGroupRepo, $router);
    }
    
    private function generateSessionKey(){
        $rand = random_bytes(4);
        return uniqid() . bin2hex($rand);
    }

    #[Route('/user/view', name:'api_user_view', methods:['GET', 'POST'])]
    public function userView(Request $request, ValidatorInterface $validator) : Response{

        $session = $request->getSession();

        if($session->get('email') === null)
            return $this->redirectToRoute('api_login_form');

        $userCredentials = new UserCredentials();

        $userCredentials->setEmail($session->get('email'));
        $userCredentials->setName($session->get('name'));
        $userCredentials->setZip($session->get('zip'));
        $userCredentials->setPlace($session->get('place'));
        $userCredentials->setPhone($session->get('phone'));

        $form = $this->createFormBuilder($userCredentials)
            ->add('email', TextType::class)
            ->add('name', TextType::class)
            ->add('zip', IntegerType::class)
            ->add('place', TextType::class)
            ->add('phone', TextType::class)
            ->add('password', PasswordType::class, ['required' => false])
            ->add('repeat', PasswordType::class, ['required' => false])
            ->add('submit', SubmitType::class, ['label' => 'Update'])
            ->getForm();
        $form->handleRequest($request);
        $error = false;
        $success = false;

        if($form->isSubmitted() && $form->isValid()) {

            $session = $request->getSession();

            $status = $this->service->updateUser($session->get('sessionKey'), null, $userCredentials->getEmail(), $userCredentials->getName(), $userCredentials->getZip(), $userCredentials->getPlace(), $userCredentials->getPhone(), $userCredentials->getPassword(), null, $validator);

            if($status === true || $status === 'discarded')
                $success = 'Updated successfully!';
            else if($status === 'missing privileges')
                $error = 'Not enough privileges to update user.';
            else if($status === 'conflict')
                $error = 'The given email is already in use.';
            else 
                $error = 'An error occured. Please try again later.';
            
            return $this->render('user.html.twig', [
                'pageTitle' => "Users",
                'menuPoints' => $this->menuPoints,
                'currentPoint' => '/user/view',
                'form' => $form,
                'error' => $error,
                'success' => $success
            ]);
        }

        return $this->render('user.html.twig', [
            'pageTitle' => "Users",
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/view',
            'form' => $form,
            'error' => $error,
            'success' => $success
        ]);
    }

    #[Route('/user', name:'api_user_get', methods:['GET'])]
    public function searchUsers(Request $request) : Response{

        $users = $this->service->searchUsers($request->query);

        return $this->json($users);
    }

    #[Route('/user', name:'api_user_post', methods:['POST'])]
    public function addUser(ValidatorInterface $validator, MailerInterface $mailer, Request $request) : Response{

        $emailAddress = $request->request->get('email');
        if($this->service->getUser($emailAddress)){
            throw new ConflictHttpException('user with email "' . $emailAddress . '" does already exist');
        }

        $password = $request->request->get('password');
        if($password === null || strlen($password) < 8){
            throw new BadRequestHttpException('post user requests require the password request parameter to be set and have a length of at least 8');
        }

        $user = $this->service->getValidateUser($emailAddress, $request->request->get('name'), $request->request->get('zip'), $request->request->get('place'), $request->request->get('phone'), $password, $validator);

        if(!$user){
            throw new BadRequestHttpException('post user requests require the email, name, zip, place and phone request parameters to be set and have valid values');
        }

        $this->service->signupUser($user, $mailer);

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/user', name:'api_user_update', methods:['PUT'])]
    public function updateUser(ValidatorInterface $validator, Request $request) : Response{

        $sessionKey = $request->request->get('session');
        if($sessionKey === null){
            throw new BadRequestHttpException('update user requests require the session request parameter to be set');
        }

        $status = $this->service->updateUser($sessionKey, $request->request->get('targetemail'), $request->request->get('email'), $request->request->get('name'), $request->request->get('zip'), $request->request->get('place'), $request->request->get('phone'), $request->request->get('passwort'), $request->request->get('group'), $validator);

        $response = new Response();

        if($status === 'not found')
            throw new NotFoundHttpException('update user target could not be found');
        else if($status === 'missing privileges')
            throw new HttpException(403, 'not eneugh privileges');
        else if($status === 'conflict')
            throw new ConflictHttpException('another user with the specified email does already exist');
        else if($status === 'invalid cred')
            throw new BadRequestHttpException('update user requests require a valid email, name, zip, place and phone request parameter if set');
        else if($status === 'invalid group')
            throw new BadRequestHttpException('update user requests require a valid group request parameter if set');
        else if($status === 'discarded')
            $response->setStatusCode(206);
        else if($status)
            $response->setStatusCode(200);

        return $response;
    }
    
    #[Route('/user', name:'api_user_delete', methods:['DELETE'])]
    public function deleteUser(Request $request) : Response{

        $sessionKey = $request->request->get('session');
        $targetemail = $request->request->get('target');
    
        if($sessionKey === null || $targetemail === null){
            throw new BadRequestHttpException('delete user requests require the session and target query parameter to be set');
        }

        $status = $this->service->deleteUser($sessionKey, $targetemail);

        if($status === 'missing privileges')
            throw new HttpException(403, 'not enough privileges');
        else if($status === 'not found')
            throw new NotFoundHttpException('delete user target could not be found');

        $response = new Response();
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/varify', name:'api_user_varify', methods:['GET'])]
    public function validateUser(Request $request) : Response{
        
        $activateKey = $request->query->get('key');
        if($activateKey === null){
            throw new BadRequestHttpException('varify requests require the key query parameter to be set');
        }

        if($this->service->validateUser()){
            return $this->redirect($this->getAbsoluteFrontendAddress('/#/activated'));
        }else{
            return $this->redirect($this->getAbsoluteFrontendAddress('/#/invalid'));
        }
    }

    private function getAbsoluteFrontendAddress($relative){
        return 'http://' . $this->frontendAddress . ':' . $this->frontendPort . $relative;
    }

    #[Route('/usergroup', name:'api_usergroup_get', methods:['GET'])]
    public function getUsergroup(Request $request) : Response{

        $groupname = $request->query->get('group');
        if($groupname === null){
            throw new BadRequestHttpException('get usergroup requests require the group query parameter to be set');
        }

        $usergroup = $this->service->getUsergroup($groupname);
        if(!$usergroup){
            return new NotFoundHttpException('get usergroup target ' . $groupname . ' could not be found');
        }

        return $this->json($usergroup);
    }
}

?>