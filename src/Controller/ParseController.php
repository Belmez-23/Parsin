<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ParseController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('parse/index.html.twig', [
            'controller_name' => 'ParseController',
        ]);
    }

    #[Route('/parser', name: 'parser')]
    public function parse(Environment $twig, Request $request, CategoryRepository $categoryRepository): Response
    {
        $url = $request->query->get('url');
        if(is_null($url)){
            return new Response($twig->render('parse/parse.html.twig'));
        } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
            //получить И ПРОВЕРИТЬ страницу по урл, найти объекты, вставить в базу, редирект по новому айди
            $client = new Client();
            //$response = $client->get($url);
            $res = $client->request('GET', $url, ['allow_redirects' => false]);
            $con = $res->getBody()->getContents();
            $craw = new Crawler($con);
            $craw_cat = $craw->filterXPath('//body/div[1]/div[7]/div[2]/main/h1'); //путь к заголовку
            // заменить на Хпаз ОЗОНа, мейби вынести в конструкт / отд класс
            $category_name = $craw_cat->text();
            //взять заголовок: если не существует - добавить, если существует - найти)
            if(!($categoryRepository->findOneByName($category_name))){
                $category = new Category();
                $category->setName($category_name);
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            } else {
                $category = $categoryRepository->findOneByName($category_name);
            }

            $craw_pro = $craw->filterXPath('form')->each(function(Crawler $craw, $category, $p) {
                $product = new Product();
                $product->setName($craw->filterXPath('form/div/div[2]/div[1]/div[1]')->text());
                $product->setPrice($craw->filterXPath('div/div[2]/div[1]/div[3]/div/div')->text());
                $product->setUrl($craw->filterXPath('form/div/div[2]/div[1]/div[1]')->attr('href'));
                $category->addProduct($product);
                $this->entityManager->persist($product);
                $this->entityManager->flush();
            }
            );

            return $this->redirect('/parser/'.$category->getId());
        }
        else return new Response("Wrong URL address");
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
