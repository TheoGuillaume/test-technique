<?php

namespace App\Twig\Components;

use App\Entity\Product;
use App\Entity\PromoCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class PromoCodeComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Product $product;

    #[LiveProp(writable: true)]
    public ?string $code = null;

    #[LiveProp]
    public ?float $discountedPrice = null;

    #[LiveProp]
    public ?string $errorMessage = null;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[LiveAction]
    public function updateCode(): void
    {
        if (empty($this->code)) {
            $this->discountedPrice = null;
            $this->errorMessage = null;
            return;
        }

        $promoCode = $this->em->getRepository(PromoCode::class)->findOneBy([
            'code' => $this->code,
            'product' => $this->product
        ]);

        if (!$promoCode) {
            $this->errorMessage = 'Code promo invalide ou non applicable à ce produit.';
            $this->discountedPrice = null;
            return;
        }

        if ($promoCode->getDateExpiration() < new \DateTimeImmutable()) {
            $this->errorMessage = 'Ce code promo est expiré.';
            $this->discountedPrice = null;
            return;
        }

        $this->errorMessage = null;
        $reductionAmount = $this->product->getPrice() * ($promoCode->getPourcentage() / 100);
        $this->discountedPrice = $this->product->getPrice() - $reductionAmount;
    }
}