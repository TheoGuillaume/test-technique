<?php

namespace App\Controller\Product;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShowProductController extends AbstractController
{
    #[Route('/products/{id}', name: 'product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
