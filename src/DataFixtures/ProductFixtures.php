<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Produits avec des données variées et réalistes
        $products = [
            [
                'name' => 'MacBook Pro 16"',
                'description' => 'Ordinateur portable haut de gamme avec processeur M3 Pro, 18 Go de RAM et 512 Go de stockage SSD. Parfait pour les développeurs et créatifs.',
                'price' => 2899.99,
                'stock' => 15
            ],
            [
                'name' => 'iPhone 15 Pro',
                'description' => 'Smartphone premium avec écran Super Retina XDR 6.1", puce A17 Pro et système photo professionnel triple caméra.',
                'price' => 1229.00,
                'stock' => 32
            ],
            [
                'name' => 'Casque Sony WH-1000XM5',
                'description' => 'Casque audio sans fil avec réduction de bruit active de pointe, autonomie 30h et qualité sonore exceptionnelle.',
                'price' => 399.99,
                'stock' => 8
            ],
            [
                'name' => 'Clavier mécanique Keychron K2',
                'description' => null, // Pas de description pour tester le champ nullable
                'price' => 89.99,
                'stock' => 0
            ],
            [
                'name' => 'Écran Dell UltraSharp 27"',
                'description' => 'Moniteur 4K professionnel avec calibrage colorimétrique d\'usine, connectivité USB-C et hub intégré pour une productivité maximale.',
                'price' => 649.50,
                'stock' => 5
            ]
        ];

        // Ajout des produits prédéfinis
        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name'])
                    ->setDescription($productData['description'])
                    ->setPrice($productData['price'])
                    ->setStock($productData['stock']);

            $manager->persist($product);
        }

        // Génération de 5 produits supplémentaires avec Faker pour plus de variété
        for ($i = 0; $i < 5; $i++) {
            $product = new Product();
            $product->setName($faker->words(3, true))
                    ->setDescription($faker->optional(0.8)->paragraph()) // 80% de chance d'avoir une description
                    ->setPrice($faker->randomFloat(2, 9.99, 999.99))
                    ->setStock($faker->numberBetween(0, 50));

            $manager->persist($product);
        }

        $manager->flush();
    }
}
