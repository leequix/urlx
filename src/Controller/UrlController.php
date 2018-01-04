<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class UrlController extends Controller
{
    /**
     * @Route("/", name="url_index")
     */
    public function index()
    {
        return $this->render('index.html.twig', ['title' => 'Urlx']);
    }

    /**
     * @Route("/url", name="url_add")
     * @Method("POST")
     */
    public function add()
    {
        return new Response("Added!");
    }
}
