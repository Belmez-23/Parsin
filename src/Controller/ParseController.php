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
    private $xp_category = 'h1';
    private $xp_product = 'div.product_bot_in1';

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->redirect('/admin');
            /*$this->render('parse/index.html.twig', [
            'controller_name' => 'ParseController',
        ]); */
    }

    #[Route('/parser', name: 'parser')]
    public function parse(Environment $twig, Request $request, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
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
            //путь к заголовку
            // заменить на Хпаз ОЗОНа, мейби вынести в конструкт / отд класс
            $category_name = $craw->filter($this->xp_category)->text();
            //взять заголовок: если не существует - добавить, если существует - найти)
            if(!($categoryRepository->findOneByName($category_name))){
                $category = new Category();
                $category->setName($category_name);
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            } else {
                $category = $categoryRepository->findOneByName($category_name);
            }

            $craw_pro = $craw->filter($this->xp_product);
            $product_count = 0;
            foreach($craw_pro as $pro){
                $cp = new Crawler($pro);
                $product = new Product();
                $pro_name = $cp->filter('div.product_name');

                if(!($productRepository->findByName($pro_name->text()))){
                    $product->setName($pro_name->text());

                    $pro_url = 'https://nyapi.ru/'.$pro_name->filter('a')->attr('href');
                    $product->setUrl($pro_url);

                    $cp = new Crawler($pro);
                    $pro_price = $cp->filter('.price-current')->text();
                    $product->setPrice($pro_price);
                    /*
                    //Артикул
                    $pro_page = $client->request('GET', $pro_url, ['allow_redirects' => false]);
                    $pro_craw = new Crawler($pro_page->getBody()->getContents());
                    $product->setUrl($pro_craw->filter('.shop2-product-article')->text());
                    */
                    $category->addProduct($product);
                    $this->entityManager->persist($product);
                    $this->entityManager->flush();
                    $product_count++;
                }
            }
            $this->addFlash('success', 'Добавлено '.$product_count.' новых товаров в категорию '.$category_name);
            return $this->redirect('/parser/'.$category->getId());
        }
        else return new Response("Неправильный ввод");
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
