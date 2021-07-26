<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ParseController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('parse/index.html.twig', [
            'controller_name' => 'ParseController',
        ]);
    }

    #[Route('/parser', name: 'parser')]
    public function parse(Environment $twig, Request $request): Response
    {
        $url = $request->query->get('url');
        if(is_null($url)){
            return new Response($twig->render('parse/parse.html.twig'));
        } else {
            return $this->redirect('/parser/'.$url);
        }
    }

    #[Route('/parser/{id}', name: 'category')]
    public function parseCategory(string $id, Category $category, Environment $twig, ProductRepository $productRepository): Response
    {

        //return new Response(sprintf("This category is: %s",  $id));
        return new Response($twig->render('parse/show.html.twig', [
            'category' => $category,
            'products' => $productRepository->findBy(['category_id' => $id]),
        ]));
    }
}
