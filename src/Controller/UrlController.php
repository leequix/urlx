<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class UrlController extends Controller
{
    /**
     * @Route("/url", name="url")
     */
    public function index()
    {
        return new Response('Welcome to your new controller!');
    }
}
