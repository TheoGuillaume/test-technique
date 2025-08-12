<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $price = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $stock = null;

    /**
     * @var Collection<int, PromoCode>
     */
    #[ORM\OneToMany(targetEntity: PromoCode::class, mappedBy: 'product')]
    private Collection $promoCodes;

    public function __construct()
    {
        $this->promoCodes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @return Collection<int, PromoCode>
     */
    public function getPromoCodes(): Collection
    {
        return $this->promoCodes;
    }

    public function addPromoCode(PromoCode $promoCode): static
    {
        if (!$this->promoCodes->contains($promoCode)) {
            $this->promoCodes->add($promoCode);
            $promoCode->setProduct($this);
        }

        return $this;
    }

    public function removePromoCode(PromoCode $promoCode): static
    {
        if ($this->promoCodes->removeElement($promoCode)) {
            // set the owning side to null (unless already changed)
            if ($promoCode->getProduct() === $this) {
                $promoCode->setProduct(null);
            }
        }

        return $this;
    }
}
