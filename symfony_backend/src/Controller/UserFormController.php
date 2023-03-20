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

class UserFormController extends AbstractController{

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

    #[Route('/user/edit', name:'api_user_edit', methods:['GET', 'POST'])]
    public function userEdit(Request $request, ValidatorInterface $validator, UrlGeneratorInterface $router) : Response{

        $session = $request->getSession();
        $email = $session->get('email');
        if($email === null)
            return $this->redirectToRoute('api_login_form');

        $targetemail = $request->query->get('targetemail');
        if($targetemail == null)throw new BadRequestHttpException('edit user requests require the targetemail query parameter to be set');
        
        if($targetemail == $email){
            $invokerPrivileges = $this->service->getUserPrivileges($email, true);
        }else{
            $invokerPrivileges = $this->service->getUserPrivileges($email, false);
        }
        if($invokerPrivileges == null){
            $session->clear();
            return $this->redirectToRoute('api_login_form');
        }
        
        $target = $this->service->getUser($targetemail);
        if($target == null)throw new NotFoundHttpException('target user for edit user request not found');

        $formData = $this->getUserForm($invokerPrivileges, $target->getEmail(), $target->getName(), $target->getZip(), $target->getPlace(), $target->getPhone(), $target->getUsergroup()->getName(), $target->getUsergroup());
        
        $form = $formData['form'];
        $userData = $formData['data'];

        if($invokerPrivileges)
            $delete = $router->generate('api_user_deleteform_confirm', ['target' => $targetemail]);

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
            
            return $this->render('user.html.twig', [
                'pageTitle' => "Users",
                'menuPoints' => $this->menuPoints,
                'currentPoint' => '/user/view',
                'form' => $form,
                'delete' => $delete,
                'error' => $error,
                'success' => $success
            ]);
        }

        return $this->render('user.html.twig', [
            'pageTitle' => "Users",
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/list',
            'form' => $form,
            'delete' => $delete,
            'error' => $error,
            'success' => $success
        ]);
    }

    #[Route('/signup', name:'api_user_signup', methods:['GET', 'POST'])]
    public function userSignup(MailerInterface $mailer, Request $request) : Response {
        
        $formData = $this->getUserForm();

        $form = $formData['form'];
        $userData = $formData['data'];

        $form->handleRequest($request);

        $error = false;

        if($form->isSubmitted() && $form->isValid()){

            $agreed = $form->get('userdata')->get('agree')->getData();

            if($agreed){
                $user = new User();
                $user->setData($userData, $this->service);

                try{
                    $this->service->signupUser($user, $mailer, true);
                }catch(Exception $e){
                    $error = "An error occured. Please try again later.";
                }

                if(!$error){
                    return $this->render('info.html.twig', [
                        'pageTitle' => "Signup",
                        'info' => "Your account has successfully been created. In order to activate your account use the link we provided in your email."
                    ]);
                }
            }else{
                $error = "In order to create an account you have to agree to the terms and privacy policy.";
            }
        }

        return $this->render('signup.html.twig', [
            'pageTitle' => "Signup",
            'form' => $form,
            'error' => $error
        ]);
    }

    private function getUserForm($invokerPrivileges = null, $email = "", $name = "", $zip = "", $place = "", $phone = "", $group = ""){

        if($invokerPrivileges == null){
            $signupMode = true;
        }else{
            $signupMode = false;
        }

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

        $validationGroups = ['Default'];
        if($signupMode)array_push($validationGroups, 'signup');
        

        $form = $this->createFormBuilder($userData, ['validation_groups' => $validationGroups])
            ->add('userdata', UserEditType::class, ['privileges' => $invokerPrivileges, 'signup' => $signupMode])
            ->getForm()
        ;

        return [
            'form' => $form,
            'data' => $userData
        ];
    }

    #[Route('/user/activated', name:'api_user_activated', methods:['GET'])]
    public function infoActivated(UrlGeneratorInterface $router) : Response {
        $link = $router->generate('api_login_form');
        $linkText = "To get to the login page click ";

        return $this->render('info.html.twig', [
            'pageTitle' => 'Activated',
            'info' => 'Your account has succesfully been activated.',
            'link' => $link,
            'linkText' => $linkText
        ]);
    }

    #[Route('/user/invalid', name:'api_user_activated_invalid', methods:['GET'])]
    public function infoInvalidActivation(UrlGeneratorInterface $router) : Response {
        $link = $router->generate('api_login_form');
        $linkText = "To get to the login page click ";

        return $this->render('info.html.twig', [
            'pageTitle' => 'Invalid',
            'info' => 'This link is invalid. If your account has not yet been activated please contact an administrator.',
            'link' => $link,
            'linkText' => $linkText
        ]);
    }

    #[Route('/user/delete-confirm', name:'api_user_deleteform_confirm', methods:['GET'])]
    public function getDeleteFormConfirm(UrlGeneratorInterface $router, Request $request) : Response {
        return $this->render('confirm.html.twig', [
            'pageTitle' => 'Users',
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/list',
            'query' => "Are you sure you want to delete the account with email \"" . $request->query->get('target') . "\"?",
            'onConfirm' => $router->generate('api_user_deleteform', ['target' => $request->query->get('target')])
        ]);
    }

    #[Route('/user/delete', name:'api_user_deleteform', methods:['GET'])]
    public function deleteForm(Request $request){
        $session = $request->getSession();
        $sessionKey = $session->get('sessionKey');
        if($sessionKey === null)
            return $this->redirectToRoute('api_login_form');
            
        $error = false;
        
        $targetemail = $request->query->get('target');
        if($targetemail == null){
            $error = "User does not exist.";
        }else{
            $status = $this->service->deleteUser($sessionKey, $targetemail);
        }

        if($status === 'missing privileges')
            $error = "Insufficient permissions.";
        else if($status === 'not found')
            $error = "Delete user target did not exist.";

        if(!$error){
            return $this->render('maininfo.html.twig', [
                'pageTitle' => 'Users',
                'menuPoints' => $this->menuPoints,
                'currentPoint' => '/user/list',
                'info' => "User has been successfully deleted."
            ]);
        }

        return $this->render('error.html.twig', [
            'pageTitle' => 'Users',
            'menuPoints' => $this->menuPoints,
            'currentPoint' => '/user/list',
            'error' => $error
        ]);
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
}

?>