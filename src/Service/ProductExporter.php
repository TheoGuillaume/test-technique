<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class ProductExporter
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function exportToCsv($filename = null)
    {
        // Si pas de nom de fichier, on en génère un
        if (!$filename) {
            $filename = 'products_export_' . date('Y_m_d_H_i_s') . '.csv';
        }

        // On récupère tous les produits
        $products = $this->entityManager->getRepository('App\Entity\Product')->findAll();

        // On vérifie que le dossier existe
        $exportDir = __DIR__ . '/../../public/exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filepath = $exportDir . $filename;

        // On ouvre le fichier en écriture
        $file = fopen($filepath, 'w');

        if (!$file) {
            throw new \Exception('Impossible de créer le fichier ' . $filepath);
        }

        // On écrit l'en-tête
        fputcsv($file, ['ID', 'Nom', 'Description', 'Prix', 'Stock', 'Statut Stock']);

        // On traite chaque produit
        foreach ($products as $product) {
            // On détermine le statut du stock
            $stockStatus = '';
            if ($product->getStock() == 0) {
                $stockStatus = 'Rupture';
            } elseif ($product->getStock() <= 5) {
                $stockStatus = 'Stock faible';
            } elseif ($product->getStock() <= 10) {
                $stockStatus = 'Stock moyen';
            } else {
                $stockStatus = 'Stock élevé';
            }

            // On formate le prix
            $formattedPrice = number_format($product->getPrice(), 2, ',', ' ') . ' €';

            // On traite la description (peut être null)
            $description = $product->getDescription();
            if ($description) {
                // On nettoie la description
                $description = str_replace(["\n", "\r", "\t"], ' ', $description);
                $description = trim($description);
                // On limite à 100 caractères
                if (strlen($description) > 100) {
                    $description = substr($description, 0, 97) . '...';
                }
            } else {
                $description = 'Aucune description';
            }

            // On écrit la ligne
            $row = [
                $product->getId(),
                $product->getName(),
                $description,
                $formattedPrice,
                $product->getStock(),
                $stockStatus
            ];

            fputcsv($file, $row, ';');
        }

        // On ferme le fichier
        fclose($file);

        // On calcule quelques statistiques
        $totalProducts = count($products);
        $totalValue = 0;
        $outOfStock = 0;
        $lowStock = 0;

        foreach ($products as $product) {
            $totalValue += $product->getPrice() * $product->getStock();
            if ($product->getStock() == 0) {
                $outOfStock++;
            } elseif ($product->getStock() <= 5) {
                $lowStock++;
            }
        }

        // On crée un fichier de statistiques
        $statsFile = $exportDir . 'stats_' . $filename;
        $stats = fopen($statsFile, 'w');

        fwrite($stats, "=== STATISTIQUES EXPORT PRODUITS ===\n");
        fwrite($stats, "Date d'export: " . date('d/m/Y H:i:s') . "\n");
        fwrite($stats, "Nombre total de produits: " . $totalProducts . "\n");
        fwrite($stats, "Valeur totale du stock: " . number_format($totalValue, 2, ',', ' ') . " €\n");
        fwrite($stats, "Produits en rupture: " . $outOfStock . "\n");
        fwrite($stats, "Produits en stock faible: " . $lowStock . "\n");
        fwrite($stats, "Fichier CSV généré: " . $filename . "\n");

        fclose($stats);

        // On retourne les informations
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'stats_file' => $statsFile,
            'total_products' => $totalProducts,
            'total_value' => $totalValue,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'export_time' => date('Y-m-d H:i:s')
        ];
    }

    public function getExportsList()
    {
        $exportDir = __DIR__ . '/../../public/exports/';
        $files = [];

        if (is_dir($exportDir)) {
            $handle = opendir($exportDir);
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'csv') {
                    $filepath = $exportDir . $file;
                    $files[] = [
                        'name' => $file,
                        'size' => filesize($filepath),
                        'date' => date('d/m/Y H:i:s', filemtime($filepath))
                    ];
                }
            }
            closedir($handle);
        }

        return $files;
    }
}
