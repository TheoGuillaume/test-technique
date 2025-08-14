<?php

namespace App\Tests\Service;

use App\Entity\Product;
use App\Service\ProductExporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class ProductExporterTest extends TestCase
{
    // Les propriétés mockées pour les tests
    private $entityManager;
    private $productRepository;
    private $productExporter;

    /**
     * Méthode exécutée avant chaque test
     * Initialise les mocks et le service à tester
     */
    protected function setUp(): void
    {
        // Crée un mock de l'EntityManager
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        // Crée un mock du Repository des produits
        $this->productRepository = $this->createMock(EntityRepository::class);
        
        // Configure le mock pour qu'il retourne le repository mocké quand on demande le repository Product
        $this->entityManager->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->productRepository);
            
        // Instancie le service à tester avec le mock EntityManager
        $this->productExporter = new ProductExporter($this->entityManager);
    }

    /**
     * Teste l'export CSV avec une liste vide de produits
     */
    public function testExportToCsvResponseWithEmptyData()
    {
        // Configure le repository pour retourner un tableau vide
        $this->productRepository->method('findAll')
            ->willReturn([]);
        
        // Exécute la méthode à tester
        $result = $this->productExporter->exportToCsvResponse();
        
        // Vérifie que le résultat contient toutes les clés attendues
        $this->assertArrayHasKey('csv', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('total_products', $result);
        $this->assertArrayHasKey('total_value', $result);
        $this->assertArrayHasKey('out_of_stock', $result);
        $this->assertArrayHasKey('low_stock', $result);
        
        // Vérifie le contenu du CSV
        $this->assertStringContainsString('ID;Nom;Description;Prix;Stock;"Statut Stock"', $result['csv']);
        // Vérifie le format du nom de fichier
        $this->assertMatchesRegularExpression('/products_export_\d{4}_\d{2}_\d{2}_\d{2}_\d{2}_\d{2}\.csv/', $result['filename']);
        // Vérifie les statistiques avec des données vides
        $this->assertEquals(0, $result['total_products']);
        $this->assertEquals(0.0, $result['total_value']);
        $this->assertEquals(0, $result['out_of_stock']);
        $this->assertEquals(0, $result['low_stock']);
    }

    /**
     * Teste l'export CSV avec des produits réels
     */
    public function testExportToCsvResponseWithProducts()
    {
        // Crée un premier produit de test
        $product1 = (new Product())
            ->setName('Produit 1')
            ->setDescription('Description du produit 1')
            ->setPrice(10.99)
            ->setStock(5); // Stock faible
        
        // Crée un deuxième produit de test
        $product2 = (new Product())
            ->setName('Produit 2')
            ->setDescription(null) // Description vide
            ->setPrice(25.50)
            ->setStock(0); // Rupture de stock
        
        // Définit les IDs via réflexion (car non accessibles normalement)
        $this->setPrivateId($product1, 1);
        $this->setPrivateId($product2, 2);
        
        // Configure le repository pour retourner nos produits de test
        $this->productRepository->method('findAll')
            ->willReturn([$product1, $product2]);
        
        // Exécute la méthode à tester
        $result = $this->productExporter->exportToCsvResponse();
        $csvContent = $result['csv'];
        
        // Vérifie que l'en-tête est présent
        $this->assertStringContainsString('ID;Nom;Description;Prix;Stock;"Statut Stock"', $csvContent);
        // Vérifie le premier produit dans le CSV
        $this->assertStringContainsString('1;"Produit 1";"Description du produit 1";"10,99 €";5;"Stock faible"', $csvContent);
        // Vérifie le deuxième produit dans le CSV (sans guillemets autour de "Rupture")
        $this->assertStringContainsString('2;"Produit 2";"Aucune description";"25,50 €";0;Rupture', $csvContent);
        
        // Vérifie les statistiques calculées
        $this->assertEquals(2, $result['total_products']);
        $this->assertEqualsWithDelta(10.99 * 5 + 25.50 * 0, $result['total_value'], 0.01);
        $this->assertEquals(1, $result['out_of_stock']); // 1 produit en rupture
        $this->assertEquals(1, $result['low_stock']); // 1 produit en stock faible
    }

    /**
     * Teste la méthode interne formatProductRow
     */
    public function testFormatProductRow()
    {
        // Crée un produit de test
        $product = (new Product())
            ->setName('Test Product')
            ->setDescription("Description\nwith\nnewlines") // Description avec sauts de ligne
            ->setPrice(19.99)
            ->setStock(3); // Stock faible
        
        // Définit l'ID via réflexion
        $this->setPrivateId($product, 42);
        
        // Utilise la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->productExporter);
        $method = $reflection->getMethod('formatProductRow');
        $method->setAccessible(true);
        
        // Appelle la méthode privée
        $result = $method->invoke($this->productExporter, $product);
        
        // Résultat attendu
        $expected = [
            42,
            'Test Product',
            'Description with newlines', // Sauts de ligne remplacés
            '19,99 €', // Prix formaté
            3,
            'Stock faible' // Statut déterminé
        ];
        
        // Vérifie que le formatage est correct
        $this->assertEquals($expected, $result);
    }

    /**
     * Teste la méthode interne cleanDescription
     */
    public function testCleanDescription()
    {
        // Accède à la méthode privée via réflexion
        $reflection = new \ReflectionClass($this->productExporter);
        $method = $reflection->getMethod('cleanDescription');
        $method->setAccessible(true);
        
        // Teste le nettoyage des sauts de ligne et tabulations
        $result = $method->invoke($this->productExporter, "Line1\nLine2\rLine3\tTab");
        $this->assertEquals('Line1 Line2 Line3 Tab', $result);
        
        // Teste le tronquage des descriptions trop longues
        $longText = str_repeat('a', 150);
        $result = $method->invoke($this->productExporter, $longText);
        $this->assertEquals(100, strlen($result)); // Doit être tronqué à 100 caractères
        $this->assertStringEndsWith('...', $result); // Avec '...' à la fin
    }

    /**
     * Teste la méthode interne getStockStatus
     */
    public function testGetStockStatus()
    {
        // Accède à la méthode privée via réflexion
        $reflection = new \ReflectionClass($this->productExporter);
        $method = $reflection->getMethod('getStockStatus');
        $method->setAccessible(true);
        
        // Teste tous les cas possibles de statut de stock
        $this->assertEquals('Rupture', $method->invoke($this->productExporter, 0));
        $this->assertEquals('Stock faible', $method->invoke($this->productExporter, 1));
        $this->assertEquals('Stock faible', $method->invoke($this->productExporter, 5));
        $this->assertEquals('Stock moyen', $method->invoke($this->productExporter, 6));
        $this->assertEquals('Stock moyen', $method->invoke($this->productExporter, 10));
        $this->assertEquals('Stock élevé', $method->invoke($this->productExporter, 11));
    }

    /**
     * Méthode helper pour définir l'ID d'un produit via réflexion
     * (car l'ID est normalement géré par Doctrine et non modifiable directement)
     */
    private function setPrivateId(Product $product, int $id): void
    {
        $reflection = new \ReflectionClass($product);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true); // Rend la propriété accessible
        $property->setValue($product, $id); // Définit la valeur
    }
}