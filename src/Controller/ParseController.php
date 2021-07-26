<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParseController extends AbstractController
{
    #[Route('/', name: 'parse')]
    public function index(): Response
    {
        return $this->render('parse/index.html.twig', [
            'controller_name' => 'ParseController',
        ]);
    }
}