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
    }

    #[Route('/parser', name: 'parser')]
    public function parse(Environment $twig, Request $request, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $url = $request->query->get('url');

        if(is_null($url)){
            return new Response($twig->render('parse/parse.html.twig'));
        } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
            $client = new Client();
            $res = $client->request('GET', $url, ['allow_redirects' => false]);
            $contents = $res->getBody()->getContents();
            $craw = new Crawler($contents);

            $categoryName = $craw->filter($this->xp_category)->text();

            $category = $categoryRepository->findOneByName($categoryName);
            if(!$category){
                $category = new Category();
                $category->setName($categoryName);
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            }

            $crawProduct = $craw->filter($this->xp_product);
            $productCount = 0;
            foreach($crawProduct as $pro){
                $cp = new Crawler($pro);
                $product = new Product();
                $productName = $cp->filter('div.product_name');

                if(!($productRepository->findByName($productName->text()))){
                    $product->setName($productName->text());

                    $productUrl = 'https://nyapi.ru/'.$productName->filter('a')->attr('href');
                    $product->setUrl($productUrl);

                    $productPrice = $cp->filter('.price-current')->text();
                    $product->setPrice($productPrice);

                    $product->setCategory($category);
                    $this->entityManager->persist($product);
                    $this->entityManager->flush();
                    $productCount++;
                }
            }
            $this->addFlash('success', 'Добавлено '.$productCount.' новых товаров в категорию '.$categoryName);

            return $this->redirect('/parser/'.$category->getId());

        } else {
            return new Response("Неправильный ввод");
        }
    }

    #[Route('/parser/{id}', name: 'category')]
    public function parseCategory(string $id, Category $category, Environment $twig, ProductRepository $productRepository): Response
    {
        return new Response($twig->render('parse/show.html.twig', [
            'category' => $category,
            'products' => $productRepository->findBy(['category' => $id]),
        ]));
    }
}
