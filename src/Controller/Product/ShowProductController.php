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
        $form = $this->createForm(PromoCodeType::class);
        $form->handleRequest($request);
        $discountedPrice = null;
        if( $form->isSubmitted() && $form->isValid()) {
            $promoCode = $form->getData()['code'];
             // Chercher le code promo lié à ce produit
            $promoCode = $em->getRepository(PromoCode::class)->findOneBy([
                'code' => $promoCode,
                'product' => $product
            ]);

            if(!$promoCode) {
                $this->addFlash('error', 'Code promo invalide ou non applicable à ce produit.');
            } elseif ($promoCode->getDateExpiration() < new \DateTimeImmutable()) {
                $this->addFlash('error', 'Ce code promo est expiré.');
            } else {
                // Calcul prix remisé
                //$discountedPrice = $product->getPrice() * (1 - $promoCode->getPourcentage() / 100);
                $reductionAmount = $product->getPrice() * ($promoCode->getPourcentage() / 100);
                $discountedPrice = $product->getPrice() - $reductionAmount;
            }
           
        }
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'discountedPrice' => $discountedPrice,
        ]);
    }
}
