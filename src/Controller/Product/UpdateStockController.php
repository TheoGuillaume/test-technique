<?php

namespace App\Controller\Product;

use App\Entity\Product;
use App\Form\ProductStockType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateStockController extends AbstractController
{
    #[Route('/products/{id}/edit-stock', name: 'product_edit_stock', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function __invoke(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductStockType::class, $product);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Stock mis à jour avec succès.');

            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        return $this->render('product/edit_stock.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
}
