<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Продукт')
            ->setEntityLabelInPlural('Продукты')
            ->setSearchFields(['name', 'name'])
            ->setDefaultSort(['category' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
            return $filters
                    ->add(EntityFilter::new('category'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('category');
        yield TextField::new('name');
        yield TextField::new('price');
        yield TextField::new('url');
        yield TextField::new('sku');

    }

}
