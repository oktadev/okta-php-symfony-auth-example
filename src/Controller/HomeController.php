<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\OktaApiService;

class HomeController extends AbstractController
{

    private $session;
    private $okta;
    private $userRepository;

    public function __construct(
        SessionInterface $session,
        OktaApiService $okta,
        UserRepository $UserRepository)
    {
        $this->session = $session;
        $this->okta = $okta;
        $this->userRepository = $UserRepository;
    }

    /**
    * @Route("/", name="home")
    */
    public function home()
    {
        return $this->render('home.html.twig');
    }

    /**
    * @Route("/personal-home-page", name="personal")
    */
    public function personal()
    {
        return $this->render('personal.html.twig');
    }

    /**
    * @Route("/login", name="login")
    */
    public function login()
    {
        return $this->redirect($this->okta->buildAuthorizeUrl());
    }

    /**
    * @Route("/authorization-code/callback", name="callback")
    */
    public function callback()
    {
        $accessToken = $this->session->get('access_token');
        $token = $this->okta->authorizeUser();

        if (! $token) {
            return new Response(json_encode([]), 401);
        }

        $email = $token->username;
        $user = $this->userRepository->findOneByEmail($email);

        if (! $user) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($email);
            $user->setToken($accessToken);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }

        // Manually authenticate the user
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));

        return $this->redirectToRoute('home');
    }

    /**
    * @Route("/logout", name="logout")
    */
    public function logout()
    {

    }
}
