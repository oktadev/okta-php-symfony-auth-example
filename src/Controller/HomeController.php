<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
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
        return;
    }
}
