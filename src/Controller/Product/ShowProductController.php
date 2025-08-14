<?php

namespace App\Controller\Product;

use App\Entity\Product;
use App\Entity\PromoCode;
use App\Form\PromoCodeType;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class ShowProductController extends AbstractController
{
    #[Route('/products/{id}', name: 'product_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function __invoke(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product
        ]);
    }
}
