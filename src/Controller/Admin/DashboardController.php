<?php

namespace App\Controller\Admin;

use App\Entity\Author;
use App\Entity\Magazine;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        $routeBuilder = $this->get(CrudUrlGenerator::class)->build();

        return $this->redirect($routeBuilder->setController(AuthorCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Alfa Group');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToCrud('Журналы', 'fa fa-tags', Magazine::class),
            MenuItem::linkToCrud('Авторы', 'fa fa-file-text', Author::class),
        ];
    }
}
