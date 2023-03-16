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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\User;
use App\Entity\Form\UserSearch;

use App\Entity\Form\UserData;
use App\Entity\Form\UserDataWrapper;
use App\Entity\Form\UserCredentials;
use App\Entity\Form\UserPassword;
use App\Entity\Form\UserPrivileges;

use App\Form\UserEditType;

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
        $email = $session->get('email');
        if($email === null)
            return $this->redirectToRoute('api_login_form');

        $invokerPrivileges = $this->service->getUserPrivileges($email, true);
        if($invokerPrivileges == null){
            $session->clear();
            return $this->redirectToRoute('api_login_form');
        }

        $formData = $this->getUserForm(
            $invokerPrivileges, 
            $session->get('email'), 
            $session->get('name'), 
            $session->get('zip'), 
            $session->get('place'), 
            $session->get('phone'), 
            $session->get('group')
        );

        $form = $formData['form'];
        $userData = $formData['data'];

        $form->handleRequest($request);
        $error = false;
        $success = false;

        if($form->isSubmitted() && $form->isValid()) {

            $status = $this->service->updateUserWithData($session->get('sessionKey'), null, $userData, $validator);

            if($status === true || $status === 'discarded')
                $success = 'Updated successfully!';
            else if($status === 'missing privileges')
                $error = 'Not enough privileges to update user.';
            else if($status === 'conflict')
                $error = 'The given email is already in use.';
            else 
                $error = 'An error occured. Please try again later.';
            
            $user = $this->service->getUserBySession($session->get('sessionKey'));
            $session->set('email', $user->getEmail());
            $session->set('name', $user->getName());
            $session->set('zip', $user->getZip());
            $session->set('place', $user->getPlace());
            $session->set('phone', $user->getPhone());
            $session->set('group', $user->getUsergroup()->getName());
            
            return $this->render('userform.html.twig', [
                'pageTitle' => "Users",
                'menuPoints' => $this->menuPoints,
                'currentPoint' => '/user/view',
                'form' => $form,
                'error' => $error,
                'success' => $success
            ]);
        }

        return $this->render('userform.html.twig', [
            'pageTitle' => "Users",
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/view',
            'form' => $form,
            'error' => $error,
            'success' => $success
        ]);
    }

    #[Route('/user/edit', name:'api_user_edit', methods:['GET', 'POST'])]
    public function userEdit(Request $request) : Response{

        $session = $request->getSession();
        $email = $session->get('email');
        if($email === null)
            return $this->redirectToRoute('api_login_form');
        $invokerPrivileges = $this->service->getUserPrivileges($email);
        if($invokerPrivileges == null){
            $session->clear();
            return $this->redirectToRoute('api_login_form');
        }

        $targetemail = $request->query->get('targetemail');
        if($targetemail == null)throw new BadRequestHttpException('edit user requests require the targetemail query parameter to be set');
        $target = $this->service->getUser($targetemail);
        if($target == null)throw new NotFoundHttpException('target user for edit user request not found');

        $formData = $this->getUserForm($invokerPrivileges, $target->getEmail(), $target->getName(), $target->getZip(), $target->getPlace(), $target->getPhone(), $target->getUsergroup()->getName(), $target->getUsergroup());
        
        $form = $formData['form'];
        $userData = $formData['data'];

        $form->handleRequest($request);
        $error = false;
        $success = false;

        if($form->isSubmitted() && $form->isValid()) {

            $status = $this->service->updateUserWithData($session->get('sessionKey'), $targetemail, $userData, $validator);

            if($status === true || $status === 'discarded')
                $success = 'Updated successfully!';
            else if($status === 'missing privileges')
                $error = 'Not enough privileges to update user.';
            else if($status === 'conflict')
                $error = 'The given email is already in use.';
            else 
                $error = 'An error occured. Please try again later.';
            
            return $this->render('userform.html.twig', [
                'pageTitle' => "Users",
                'menuPoints' => $this->menuPoints,
                'currentPoint' => '/user/view',
                'form' => $form,
                'error' => $error,
                'success' => $success
            ]);
        }

        return $this->render('userform.html.twig', [
            'pageTitle' => "Users",
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/list',
            'form' => $form,
            'error' => $error,
            'success' => $success
        ]);
    }

    private function getUserForm($invokerPrivileges, $email = "", $name = "", $zip = "", $place = "", $phone = "", $group = ""){

        $userData = new UserData();

        $userCredentials = new UserCredentials();
        $userCredentials->setEmail($email);
        $userCredentials->setName($name);
        $userCredentials->setZip($zip);
        $userCredentials->setPlace($place);
        $userCredentials->setPhone($phone);

        $userPrivileges = new UserPrivileges();
        $userPrivileges->setGroup($group);

        $userData->setCredentials($userCredentials);
        $userData->setPassword(new UserPassword());
        $userData->setUsergroup($userPrivileges);

        $form = $this->createFormBuilder($userData)
            ->add('userdata', UserEditType::class, ['privileges' => $invokerPrivileges])
            ->getForm()
        ;

        return [
            'form' => $form,
            'data' => $userData
        ];
    }

    #[Route('/user/list', name:'api_user_list', methods:['GET'])]
    public function userList(Request $request) : Response {

        $session = $request->getSession();
        if($session->get('email') === null)
            return $this->redirectToRoute('api_login_form');

        $page = $request->query->get('page', 0);
        $sort = $request->query->get('sort', 'email');
        $sdir = $request->query->get('sasc', true);
        
        $search = $this->service->searchUsers($request->query, $page, 16, $sort, $sdir);

        $users = $search['result'];
        $total = $search['total'];

        return $this->render('list.html.twig', [
            'pageTitle' => "Users",
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/list',
            'users' => $users,
            'page' => $page,
            'sort' => $sort,
            'sasc' => $sdir,
            'chunk' => 16,
            'total' => $total
        ]);
    }

    #[Route('/user/search', name:'api_user_search', methods:['GET', 'POST'])]
    public function userSearch(Request $request) : Response{
        
        $session = $request->getSession();
        if($session->get('email') === null)
            return $this->redirectToRoute('api_login_form');
        
        $searchUser = new UserSearch();
    
        $searchUser->setEmail("");
        $searchUser->setName("");
        $searchUser->setZip("");
        $searchUser->setPlace("");
        $searchUser->setPhone("");

        $form = $this->createFormBuilder($searchUser)
            ->add('email', TextType::class, ['required' => false])
            ->add('name', TextType::class, ['required' => false])
            ->add('zip', TextType::class, ['required' => false])
            ->add('place', TextType::class, ['required' => false])
            ->add('phone', TextType::class, ['required' => false])
            ->add('submit', SubmitType::class, ['label' => 'Search'])
            ->getForm();
        $form->handleRequest($request);

        $error = false;

        if($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('api_user_list', ['email' => $searchUser->getEmail(), 'name' => $searchUser->getName(), 'zip' => $searchUser->getZip(), 'place' => $searchUser->getPlace(), 'phone' => $searchUser->getPhone()]);
        }
        
        return $this->render('mainform.html.twig', [
            'pageTitle' => "Users",
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/search',
            'form' => $form,
            'error' => $error
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