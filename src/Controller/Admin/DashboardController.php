<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
       // return parent::index();
        $routeBuilder = $this->get(CrudUrlGenerator::class)->build();
        $url = $routeBuilder->setController(CategoryCrudController::class)->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Parser');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Админ', 'fa fa-home');
        yield MenuItem::linktoRoute('Ввод URL', 'fas fa-home', 'parser');
        yield MenuItem::linkToCrud('Категории', 'fas fa-list', Category::class);
        yield MenuItem::linkToCrud('Продукты', 'fas fa-list', Product::class);

    }
}
