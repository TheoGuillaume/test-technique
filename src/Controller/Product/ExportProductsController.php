<?php

namespace App\Controller\Product;

use App\Service\ProductExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExportProductsController extends AbstractController
{
    #[Route('/products/export', name: 'product_export', methods: ['GET'])]
    public function __invoke(ProductExporter $productExporter): Response
    {
        try {
            $result = $productExporter->exportToCsvResponse();

            $response = new Response($result['csv']);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');

            $this->addFlash('success', sprintf(
                'Export réussi ! %d produits exportés.',
                $result['total_products']
            ));
            if ($result['out_of_stock'] > 0) {
                $this->addFlash('warning', sprintf(
                    '%d produit(s) en rupture de stock détecté(s)',
                    $result['out_of_stock']
                ));
            }
            if ($result['low_stock'] > 0) {
                $this->addFlash('info', sprintf(
                    '%d produit(s) en stock faible détecté(s)',
                    $result['low_stock']
                ));
            }

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'export : ' . $e->getMessage());
            return $this->redirectToRoute('product_list');
        }
    }
}
