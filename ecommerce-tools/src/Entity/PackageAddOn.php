<?php

namespace App\Entity;

use App\Repository\PackageAddOnRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PackageAddOnRepository::class)]
#[ORM\Table(name: 'package_add_on')]
#[ORM\HasLifecycleCallbacks]
class PackageAddOn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $identifier = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private float $priceStandard = 0.0;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private float $pricePremium = 0.0;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private int $credits = 0;

    #[ORM\Column(nullable: true)]
    private ?float $perCreditPriceStandard = null;

    #[ORM\Column(nullable: true)]
    private ?float $perCreditPricePremium = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column]
    private int $displayOrder = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeProductId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePriceIdStandard = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePriceIdPremium = null;

    #[ORM\Column(nullable: true)]
    private ?int $discount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setPerCreditPrices(): void
    {
        if ($this->credits > 0) {
            $this->perCreditPriceStandard = $this->priceStandard / $this->credits;
            $this->perCreditPricePremium = $this->pricePremium / $this->credits;
        }
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

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getPriceStandard(): float
    {
        return $this->priceStandard;
    }

    public function setPriceStandard(float $priceStandard): static
    {
        $this->priceStandard = $priceStandard;
        
        if ($this->credits > 0) {
            $this->perCreditPriceStandard = $priceStandard / $this->credits;
        }

        return $this;
    }

    public function getPricePremium(): float
    {
        return $this->pricePremium;
    }

    public function setPricePremium(float $pricePremium): static
    {
        $this->pricePremium = $pricePremium;
        
        if ($this->credits > 0) {
            $this->perCreditPricePremium = $pricePremium / $this->credits;
        }

        return $this;
    }

    public function getDiscountedPriceStandard(): float
    {
        if ($this->discount === null || $this->discount <= 0) {
            return $this->priceStandard;
        }

        return $this->priceStandard * (1 - ($this->discount / 100));
    }

    public function getDiscountedPricePremium(): float
    {
        if ($this->discount === null || $this->discount <= 0) {
            return $this->pricePremium;
        }

        return $this->pricePremium * (1 - ($this->discount / 100));
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;
        
        if ($credits > 0) {
            $this->perCreditPriceStandard = $this->priceStandard / $credits;
            $this->perCreditPricePremium = $this->pricePremium / $credits;
        }

        return $this;
    }

    public function getPerCreditPriceStandard(): ?float
    {
        return $this->perCreditPriceStandard;
    }

    public function getPerCreditPricePremium(): ?float
    {
        return $this->perCreditPricePremium;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getStripeProductId(): ?string
    {
        return $this->stripeProductId;
    }

    public function setStripeProductId(?string $stripeProductId): static
    {
        $this->stripeProductId = $stripeProductId;

        return $this;
    }

    public function getStripePriceIdStandard(): ?string
    {
        return $this->stripePriceIdStandard;
    }

    public function setStripePriceIdStandard(?string $stripePriceIdStandard): static
    {
        $this->stripePriceIdStandard = $stripePriceIdStandard;

        return $this;
    }

    public function getStripePriceIdPremium(): ?string
    {
        return $this->stripePriceIdPremium;
    }

    public function setStripePriceIdPremium(?string $stripePriceIdPremium): static
    {
        $this->stripePriceIdPremium = $stripePriceIdPremium;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get package data as an array for templates
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price_standard' => $this->getDiscountedPriceStandard(),
            'price_premium' => $this->getDiscountedPricePremium(),
            'original_price_standard' => $this->priceStandard,
            'original_price_premium' => $this->pricePremium,
            'credits' => $this->credits,
            'per_credit_price_standard' => $this->perCreditPriceStandard,
            'per_credit_price_premium' => $this->perCreditPricePremium,
            'description' => $this->description,
            'active' => $this->isActive,
            'featured' => $this->isFeatured,
            'discount' => $this->discount,
            'identifier' => $this->identifier,
            'display_order' => $this->displayOrder
        ];
    }
}