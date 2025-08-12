<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Writer;

class ProductExporter
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function exportToCsvResponse(): array
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        $csv = Writer::createFromString('');
        $csv->setDelimiter(';');
        $csv->insertOne(['ID', 'Nom', 'Description', 'Prix', 'Stock', 'Statut Stock']);

        $stats = $this->calculateExportStats($products, $csv);

        return [
            'csv' => $csv->toString(),
            'stats_text' => $this->generateStatsText($stats), // Optionnel, si tu veux garder le texte
            'filename' => 'products_export_' . date('Y_m_d_H_i_s') . '.csv',
            'total_products' => $stats['totalProducts'],
            'total_value' => $stats['totalValue'],
            'out_of_stock' => $stats['outOfStock'],
            'low_stock' => $stats['lowStock'],
        ];
    }


    /**
     * Calcule les statistiques d'export à partir de la liste des produits.
     *
     * @param Product[] $products
     * @param Writer $csv
     * @return array{
     *   totalProducts: int,
     *   totalValue: float,
     *   outOfStock: int,
     *   lowStock: int
     * }
     */
    private function calculateExportStats(array $products, Writer $csv): array
    {
        $totalProducts = 0;
        $totalValue = 0.0;
        $outOfStock = 0;
        $lowStock = 0;

        foreach ($products as $product) {
            $row = $this->formatProductRow($product);
            $csv->insertOne($row);

            $totalValue += $product->getPrice() * $product->getStock();
            $totalProducts++;

            if ($product->getStock() === 0) {
                $outOfStock++;
            } elseif ($product->getStock() <= 5) {
                $lowStock++;
            }
        }

        return [
            'totalProducts' => $totalProducts,
            'totalValue' => $totalValue,
            'outOfStock' => $outOfStock,
            'lowStock' => $lowStock,
        ];
    }

    /**
     * Formate une ligne de produit pour le CSV.
     */
    private function formatProductRow(Product $product): array
    {
        $description = $product->getDescription() ?? 'Aucune description';
        $description = $this->cleanDescription($description);

        return [
            $product->getId(),
            $product->getName(),
            $description,
            number_format($product->getPrice(), 2, ',', ' ') . ' €',
            $product->getStock(),
            $this->getStockStatus($product->getStock()),
        ];
    }

    /**
     * Nettoie et tronque la description.
     */
    private function cleanDescription(string $description): string
    {
        $description = str_replace(["\n", "\r", "\t"], ' ', $description);
        $description = trim($description);
        return strlen($description) > 100 ? substr($description, 0, 97) . '...' : $description;
    }

    /**
     * Détermine le statut du stock.
     */
    private function getStockStatus(int $stock): string
    {
        if ($stock === 0) {
            return 'Rupture';
        } elseif ($stock <= 5) {
            return 'Stock faible';
        } elseif ($stock <= 10) {
            return 'Stock moyen';
        }
        return 'Stock élevé';
    }

    /**
     * Génère le texte des statistiques d'export.
     */
   private function generateStatsText(array $stats): string
    {
        return sprintf(
            "=== STATISTIQUES EXPORT PRODUITS ===\n" .
            "Date d'export: %s\n" .
            "Nombre total de produits: %d\n" .
            "Valeur totale du stock: %s €\n" .
            "Produits en rupture: %d\n" .
            "Produits en stock faible: %d\n",
            date('d/m/Y H:i:s'),
            $stats['totalProducts'],
            number_format($stats['totalValue'], 2, ',', ' '),
            $stats['outOfStock'],
            $stats['lowStock']
        );
    }
}
